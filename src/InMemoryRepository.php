<?php

/*
 * This file is part of the puli/repository package.
 *
 * (c) Bernhard Schussek <bschussek@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Puli\Repository;

use ArrayIterator;
use Puli\Repository\Api\EditableRepository;
use Puli\Repository\Api\Resource\Resource;
use Puli\Repository\Api\ResourceCollection;
use Puli\Repository\Api\ResourceNotFoundException;
use Puli\Repository\Api\UnsupportedResourceException;
use Puli\Repository\Resource\Collection\ArrayResourceCollection;
use Puli\Repository\Resource\GenericResource;
use Webmozart\Assert\Assert;
use Webmozart\Glob\Glob;
use Webmozart\Glob\Iterator\GlobFilterIterator;
use Webmozart\Glob\Iterator\RegexFilterIterator;
use Webmozart\PathUtil\Path;

/**
 * An in-memory resource repository.
 *
 * Resources can be added with the method {@link add()}:
 *
 * ```php
 * use Puli\Repository\InMemoryRepository;
 *
 * $repo = new InMemoryRepository();
 * $repo->add('/css', new DirectoryResource('/path/to/project/res/css'));
 * ```
 *
 * @since  1.0
 *
 * @author Bernhard Schussek <bschussek@gmail.com>
 */
class InMemoryRepository extends AbstractRepository implements EditableRepository
{
    /**
     * @var Resource[]
     */
    private $resources = array();

    /**
     * Creates a new repository.
     */
    public function __construct()
    {
        $this->clear();
    }

    /**
     * {@inheritdoc}
     */
    public function get($path)
    {
        $path = $this->sanitizePath($path);

        if (!isset($this->resources[$path])) {
            throw ResourceNotFoundException::forPath($path);
        }

        return $this->resources[$path];
    }

    /**
     * {@inheritdoc}
     */
    public function find($query, $language = 'glob')
    {
        $this->validateSearchLanguage($language);

        $query = $this->sanitizePath($query);
        $resources = array();

        if (Glob::isDynamic($query)) {
            $resources = $this->getGlobIterator($query);
        } elseif (isset($this->resources[$query])) {
            $resources = array($this->resources[$query]);
        }

        return new ArrayResourceCollection($resources);
    }

    /**
     * {@inheritdoc}
     */
    public function contains($query, $language = 'glob')
    {
        $this->validateSearchLanguage($language);

        $query = $this->sanitizePath($query);

        if (Glob::isDynamic($query)) {
            $iterator = $this->getGlobIterator($query);
            $iterator->rewind();

            return $iterator->valid();
        }

        return isset($this->resources[$query]);
    }

    /**
     * {@inheritdoc}
     */
    public function add($path, $resource)
    {
        $path = $this->sanitizePath($path);

        if ($resource instanceof ResourceCollection) {
            $this->ensureDirectoryExists($path);
            foreach ($resource as $child) {
                $this->addResource($path.'/'.$child->getName(), $child);
            }

            // Keep the resources sorted by file name
            ksort($this->resources);

            return;
        }

        if ($resource instanceof Resource) {
            $this->ensureDirectoryExists(Path::getDirectory($path));
            $this->addResource($path, $resource);

            ksort($this->resources);

            return;
        }

        throw new UnsupportedResourceException(sprintf(
            'The passed resource must be a Resource or ResourceCollection. Got: %s',
            is_object($resource) ? get_class($resource) : gettype($resource)
        ));
    }

    /**
     * {@inheritdoc}
     */
    public function remove($query, $language = 'glob')
    {
        $resources = $this->find($query, $language);
        $nbOfResources = count($this->resources);

        // Run the assertion after find(), so that we know that $query is valid
        Assert::notEmpty(trim($query, '/'), 'The root directory cannot be removed.');

        foreach ($resources as $resource) {
            $this->removeResource($resource);
        }

        return $nbOfResources - count($this->resources);
    }

    /**
     * {@inheritdoc}
     */
    public function clear()
    {
        $root = new GenericResource('/');
        $root->attachTo($this);

        // Subtract root
        $removed = count($this->resources) - 1;

        $this->resources = array('/' => $root);

        return $removed;
    }

    /**
     * {@inheritdoc}
     */
    public function listChildren($path)
    {
        $iterator = $this->getChildIterator($this->get($path));

        return new ArrayResourceCollection($iterator);
    }

    /**
     * {@inheritdoc}
     */
    public function hasChildren($path)
    {
        $iterator = $this->getChildIterator($this->get($path));
        $iterator->rewind();

        return $iterator->valid();
    }

    /**
     * Recursively creates a directory for a path.
     *
     * @param string $path A directory path.
     */
    private function ensureDirectoryExists($path)
    {
        if (!isset($this->resources[$path])) {
            // Recursively initialize parent directories
            if ($path !== '/') {
                $this->ensureDirectoryExists(Path::getDirectory($path));
            }

            $this->resources[$path] = new GenericResource($path);
            $this->resources[$path]->attachTo($this);

            return;
        }
    }

    private function addResource($path, Resource $resource)
    {
        // Don't modify resources attached to other repositories
        if ($resource->isAttached()) {
            $resource = clone $resource;
        }

        $basePath = '/' === $path ? $path : $path.'/';

        // Read children before attaching the resource to this repository
        $children = $resource->listChildren();

        $resource->attachTo($this, $path);

        // Add the resource before adding its children, so that the array
        // stays sorted
        $this->resources[$path] = $resource;

        foreach ($children as $name => $child) {
            $this->addResource($basePath.$name, $child);
        }
    }

    private function removeResource(Resource $resource)
    {
        $path = $resource->getPath();

        // Ignore non-existing resources
        if (!isset($this->resources[$path])) {
            return;
        }

        // Recursively register directory contents
        foreach ($this->getChildIterator($resource) as $child) {
            $this->removeResource($child);
        }

        unset($this->resources[$path]);

        // Detach from locator
        $resource->detach();
    }

    /**
     * Returns an iterator for the children of a resource.
     *
     * @param Resource $resource The resource.
     *
     * @return RegexFilterIterator The iterator.
     */
    private function getChildIterator(Resource $resource)
    {
        $staticPrefix = rtrim($resource->getPath(), '/').'/';
        $regExp = '~^'.preg_quote($staticPrefix, '~').'[^/]+$~';

        return new RegexFilterIterator(
            $regExp,
            $staticPrefix,
            new ArrayIterator($this->resources),
            RegexFilterIterator::FILTER_KEY
        );
    }

    /**
     * Returns an iterator for a glob.
     *
     * @param string $glob The glob.
     *
     * @return GlobFilterIterator The iterator.
     */
    protected function getGlobIterator($glob)
    {
        return new GlobFilterIterator(
            $glob,
            new ArrayIterator($this->resources),
            GlobFilterIterator::FILTER_KEY
        );
    }
}
