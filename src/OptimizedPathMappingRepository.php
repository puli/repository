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
use Webmozart\Assert\Assert;
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
        // TODO
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
        // TODO
    }

    /**
     * {@inheritdoc}
     */
    public function hasChildren($path)
    {
        // TODO
    }


    /**
     * @param string $path
     * @param FilesystemResource $resource
     */
    private function addResource($path, FilesystemResource $resource)
    {
        // Add the resource before adding its children, so that the array
        // stays sorted
        $this->store->set($path, $resource);

        $basePath = '/' === $path ? $path : $path.'/';

        foreach ($resource->listChildren() as $name => $child) {
            $this->addResource($basePath.$name, $child);
        }
    }
}
