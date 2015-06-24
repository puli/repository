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

use Puli\Repository\Api\EditableRepository;
use Puli\Repository\Api\Resource\FilesystemResource;
use Puli\Repository\Api\ResourceNotFoundException;
use Puli\Repository\Api\UnsupportedResourceException;
use Puli\Repository\Resource\Collection\FilesystemResourceCollection;
use Webmozart\Assert\Assert;
use Webmozart\Glob\Iterator\RegexFilterIterator;
use Webmozart\KeyValueStore\Api\KeyValueStore;
use Webmozart\PathUtil\Path;

/**
 * Optimized path mapping repository
 * When a resource is added, all its children are fully resolved in order to
 * improve performances on usage
 *
 * @author Titouan Galopin <galopintitouan@gmail.com>
 */
class OptimizedPathMappingRepository implements EditableRepository
{
    /**
     * @var KeyValueStore
     */
    private $store;

    /**
     * Creates a new repository.
     */
    public function __construct(KeyValueStore $store)
    {
        $this->store = $store;
    }

    /**
     * {@inheritdoc}
     */
    public function get($path)
    {
        Assert::stringNotEmpty($path, 'The path must be a non-empty string. Got: %s');
        Assert::startsWith($path, '/', 'The path %s is not absolute.');

        $path = Path::canonicalize($path);

        if (! $this->store->exists($path)) {
            throw ResourceNotFoundException::forPath($path);
        }

        return $this->store->get($path);
    }

    /**
     * {@inheritdoc}
     */
    public function find($query, $language = 'glob')
    {
        // TODO
    }

    /**
     * {@inheritdoc}
     */
    public function contains($query, $language = 'glob')
    {
        // TODO
    }

    /**
     * {@inheritdoc}
     */
    public function add($path, $resource)
    {
        Assert::stringNotEmpty($path, 'The path must be a non-empty string. Got: %s');
        Assert::startsWith($path, '/', 'The path %s is not absolute.');

        $path = Path::canonicalize($path);

        if ($resource instanceof FilesystemResource) {
            $this->addResource($path, $resource);

            return;
        }

        throw new UnsupportedResourceException(sprintf(
            'The passed resource must be a FilesystemResource. Got: %s',
            is_object($resource) ? get_class($resource) : gettype($resource)
        ));
    }

    /**
     * {@inheritdoc}
     */
    public function remove($query, $language = 'glob')
    {
        $resources = $this->find($query, $language);
        $nbOfResources = count($this->store->keys());

        // Run the assertion after find(), so that we know that $query is valid
        Assert::notEq('', trim($query, '/'), 'The root directory cannot be removed.');

        foreach ($resources as $resource) {
            $this->removeResource($resource);
        }

        return $nbOfResources - count($this->store->keys());
    }

    /**
     * {@inheritdoc}
     */
    public function clear()
    {
        $removed = count($this->store->keys());

        $this->store->clear();

        return $removed;
    }

    /**
     * {@inheritdoc}
     */
    public function listChildren($path)
    {
        $iterator = $this->getChildIterator($this->get($path));
        $children = $this->store->getMultiple(iterator_to_array($iterator));

        return new FilesystemResourceCollection($children);
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
     * @param string $path
     * @param FilesystemResource $resource
     */
    private function addResource($path, FilesystemResource $resource)
    {
        // Don't modify resources attached to other repositories
        if ($resource->isAttached()) {
            $resource = clone $resource;
        }

        // Read children before attaching the resource to this repository
        $children = $resource->listChildren();

        $resource->attachTo($this, $path);

        // Add the resource before adding its children, so that the array
        // stays sorted
        $this->store->set($path, $resource);

        $basePath = '/' === $path ? $path : $path.'/';

        foreach ($children as $name => $child) {
            $this->addResource($basePath.$name, $child);
        }
    }


    /**
     * @param FilesystemResource $resource
     */
    private function removeResource(FilesystemResource $resource)
    {
        $path = $resource->getPath();

        // Ignore non-existing resources
        if (! $this->store->exists($path)) {
            return;
        }

        // Recursively register directory contents
        foreach ($this->getChildIterator($resource) as $child) {
            $this->removeResource($child);
        }

        $this->store->remove($path);

        // Detach from locator
        $resource->detach($this);
    }

    /**
     * {@inheritdoc}
     */
    private function getChildIterator(FilesystemResource $resource)
    {
        $staticPrefix = rtrim($resource->getPath(), '/').'/';
        $regExp = '~^'.preg_quote($staticPrefix, '~').'[^/]+$~';

        return new RegexFilterIterator(
            $regExp,
            $staticPrefix,
            new \ArrayIterator($this->store->keys())
        );
    }
}
