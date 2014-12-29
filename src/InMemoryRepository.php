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
use InvalidArgumentException;
use Puli\Repository\Api\EditableRepository;
use Puli\Repository\Api\Resource\Resource;
use Puli\Repository\Api\ResourceCollection;
use Puli\Repository\Api\ResourceNotFoundException;
use Puli\Repository\Api\ResourceRepository;
use Puli\Repository\Api\UnsupportedLanguageException;
use Puli\Repository\Api\UnsupportedResourceException;
use Puli\Repository\Assert\Assertion;
use Puli\Repository\Resource\Collection\ArrayResourceCollection;
use Puli\Repository\Resource\GenericResource;
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
 * Alternatively, another repository can be passed as "backend". The paths of
 * this backend can be passed to the second argument of {@link add()}. By
 * default, a {@link FilesystemRepository} is used:
 *
 * ```php
 * use Puli\Repository\InMemoryRepository;
 *
 * $repo = new InMemoryRepository();
 * $repo->add('/css', '/path/to/project/res/css');
 * ```
 *
 * You can also create the backend manually and pass it to the constructor:
 *
 * ```php
 * use Puli\Repository\FilesystemRepository;
 * use Puli\Repository\InMemoryRepository;
 *
 * $backend = new FilesystemRepository('/path/to/project');
 *
 * $repo = new InMemoryRepository($backend)
 * $repo->add('/css', '/res/css');
 * ```
 *
 * @since  1.0
 * @author Bernhard Schussek <bschussek@gmail.com>
 */
class InMemoryRepository implements EditableRepository
{
    /**
     * @var Resource[]|\Puli\Repository\Api\\Puli\Repository\Api\Resource\DirectoryResource[]
     */
    private $resources = array();

    /**
     * @var ResourceRepository
     */
    private $backend;

    /**
     * Creates a new repository.
     *
     * The backend repository is used to lookup the paths passed to the
     * second argument of {@link add}. If none is passed, a
     * {@link FilesystemRepository} will be used.
     *
     * @param ResourceRepository $backend The backend repository.
     *
     * @see ResourceRepository
     */
    public function __construct(ResourceRepository $backend = null)
    {
        $this->backend = $backend ?: new FilesystemRepository();

        $this->clear();
    }

    /**
     * {@inheritdoc}
     */
    public function get($path)
    {
        Assertion::path($path);

        $path = Path::canonicalize($path);

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
        if ('glob' !== $language) {
            throw UnsupportedLanguageException::forLanguage($language);
        }

        Assertion::glob($query);

        $query = Path::canonicalize($query);
        $resources = array();

        if (false !== strpos($query, '*')) {
            $resources = iterator_to_array(new GlobFilterIterator(
                $query,
                new ArrayIterator($this->resources),
                GlobFilterIterator::FILTER_KEY
            ));
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
        if ('glob' !== $language) {
            throw UnsupportedLanguageException::forLanguage($language);
        }

        Assertion::glob($query);

        $query = Path::canonicalize($query);

        if (false !== strpos($query, '*')) {
            $iterator = new GlobFilterIterator(
                $query,
                new ArrayIterator($this->resources),
                GlobFilterIterator::FILTER_KEY
            );
            $iterator->rewind();

            return $iterator->valid();
        }

        return isset($this->resources[$query]);
    }

    /**
     * {@inheritdoc}
     *
     * If a path is passed as second argument, the added resources are fetched
     * from the backend passed to {@link __construct}.
     *
     * @param string                             $path     The path at which to
     *                                                     add the resource.
     * @param string|Resource|\Puli\Repository\Api\ResourceCollection $resource The resource(s) to
     *                                                     add at that path.
     *
     * @throws InvalidArgumentException If the path is invalid. The path must be
     *                                  a non-empty string starting with "/".
     * @throws UnsupportedResourceException If the resource is invalid.
     */
    public function add($path, $resource)
    {
        Assertion::path($path);

        $path = Path::canonicalize($path);

        if (is_string($resource)) {
            // Use find() only if the string is actually a glob. We want
            // deterministic results when using a glob, even if the glob
            // just matches one result.
            // See https://github.com/puli/puli/issues/17
            if (false !== strpos($resource, '*')) {
                $resource = $this->backend->find($resource);
            } else {
                $resource = $this->backend->get($resource);
            }
        }

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
            'The passed resource must be a string, Resource or '.
            'ResourceCollection. Got: %s',
            is_object($resource) ? get_class($resource) : gettype($resource)
        ));
    }

    /**
     * {@inheritdoc}
     */
    public function remove($query, $language = 'glob')
    {
        if ('glob' !== $language) {
            throw UnsupportedLanguageException::forLanguage($language);
        }

        Assertion::glob($query);

        $query = Path::canonicalize($query);

        Assertion::notEq('/', $query, 'The root directory cannot be removed.');

        $resourcesToRemove = array();
        $removed = 0;

        if (false !== strpos($query, '*')) {
            $resourcesToRemove = new GlobFilterIterator(
                $query,
                new ArrayIterator($this->resources),
                GlobFilterIterator::FILTER_KEY
            );
        } elseif (isset($this->resources[$query])) {
            $resourcesToRemove[] = $this->resources[$query];
        }

        foreach ($resourcesToRemove as $resource) {
            $this->removeResource($resource, $removed);
        }

        return $removed;
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
        Assertion::path($path);

        $path = Path::canonicalize($path);

        if (!isset($this->resources[$path])) {
            throw ResourceNotFoundException::forPath($path);
        }

        $staticPrefix = rtrim($path, '/').'/';
        $regExp = '~^'.preg_quote($staticPrefix, '~').'[^/]+$~';

        $resources = iterator_to_array(new RegexFilterIterator(
            $regExp,
            $staticPrefix,
            new ArrayIterator($this->resources),
            RegexFilterIterator::FILTER_KEY
        ));

        return new ArrayResourceCollection($resources);
    }

    /**
     * {@inheritdoc}
     */
    public function hasChildren($path)
    {
        Assertion::path($path);

        $path = Path::canonicalize($path);

        if (!isset($this->resources[$path])) {
            throw ResourceNotFoundException::forPath($path);
        }

        $staticPrefix = rtrim($path, '/').'/';
        $regExp = '~^'.preg_quote($staticPrefix, '~').'[^/]+$~';

        $iterator = new RegexFilterIterator(
            $regExp,
            $staticPrefix,
            new ArrayIterator($this->resources)
        );
        $iterator->rewind();

        return $iterator->valid();
    }

    /**
     * Recursively creates a directory for a path.
     *
     * @param string $path A directory path.
     *
     * @throws NoDirectoryException If a resource with that path exists, but is
     *                              no directory.
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
        $children = $resource->listChildren();

        // Attach resource to locator *after* calling listChildren(), because
        // this method usually depends on the previously attached repository
        $resource->attachTo($this, $path);

        // Add the resource before adding nested resources, so that the
        // array stays sorted
        $this->resources[$path] = $resource;

        // Recursively attach directory contents
        foreach ($children as $name => $child) {
            $this->addResource($basePath.$name, $child);
        }
    }

    private function removeResource(Resource $resource, &$counter)
    {
        // Ignore non-existing resources
        if (!isset($this->resources[$resource->getPath()])) {
            return;
        }

        // Recursively register directory contents
        foreach ($this->listChildren($resource->getPath()) as $child) {
            $this->removeResource($child, $counter);
        }

        unset($this->resources[$resource->getPath()]);

        // Detach from locator
        $resource->detach($this);

        // Keep track of the number of removed resources
        ++$counter;
    }
}
