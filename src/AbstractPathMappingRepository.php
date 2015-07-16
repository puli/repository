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

use Countable;
use Puli\Repository\Api\EditableRepository;
use Puli\Repository\Resource\DirectoryResource;
use Puli\Repository\Resource\FileResource;
use Puli\Repository\Resource\GenericResource;
use Webmozart\KeyValueStore\Api\KeyValueStore;
use Webmozart\PathUtil\Path;

/**
 * Abstract base for Path mapping repositories.
 *
 * @since  1.0
 *
 * @author Bernhard Schussek <bschussek@gmail.com>
 * @author Titouan Galopin <galopintitouan@gmail.com>
 */
abstract class AbstractPathMappingRepository implements EditableRepository
{
    /**
     * @var KeyValueStore
     */
    protected $store;

    /**
     * Creates a new repository.
     *
     * @param KeyValueStore $store The store of all the paths.
     */
    public function __construct(KeyValueStore $store)
    {
        $this->store = $store;

        $this->createRoot();
    }

    /**
     * {@inheritdoc}
     */
    public function clear()
    {
        // Subtract root
        $removed = $this->countStore() - 1;

        $this->store->clear();
        $this->createRoot();

        return $removed;
    }

    /**
     * Recursively creates a directory for a path.
     *
     * @param string $path A directory path.
     */
    protected function ensureDirectoryExists($path)
    {
        if ($this->store->exists($path)) {
            return;
        }

        // Recursively initialize parent directories
        if ('/' !== $path) {
            $this->ensureDirectoryExists(Path::getDirectory($path));
        }

        $this->store->set($path, null);
    }

    /**
     * Create the repository root.
     */
    protected function createRoot()
    {
        if ($this->store->exists('/')) {
            return;
        }

        $this->store->set('/', null);
    }

    /**
     * Count the number of elements in the store.
     *
     * @return int
     */
    protected function countStore()
    {
        if ($this->store instanceof Countable) {
            return count($this->store);
        }

        return count($this->store->keys());
    }

    /**
     * Sort the store by keys.
     */
    protected function sortStore()
    {
        $resources = $this->store->getMultiple($this->store->keys());

        ksort($resources);

        $this->store->clear();

        foreach ($resources as $path => $resource) {
            $this->store->set($path, $resource);
        }
    }

    /**
     * Create a resource using its filesystem path.
     *
     * If the filesystem path is a directory, a DirectoryResource will be created.
     * If the filesystem path is a file, a FileResource will be created.
     * If the filesystem does not exists, a GenericResource will be created.
     *
     * @param string $filesystemPath The filesystem path.
     *
     * @return GenericResource|DirectoryResource|FileResource The created resource.
     */
    protected function createResource($filesystemPath)
    {
        if ($filesystemPath === null) {
            return new GenericResource();
        }

        if (is_dir($filesystemPath)) {
            return new DirectoryResource($filesystemPath);
        } elseif (is_file($filesystemPath)) {
            return new FileResource($filesystemPath);
        }

        return new GenericResource();
    }

    /**
     * Create a collection of resources using a list of filesystem paths.
     *
     * @param array $filesystemPaths
     *
     * @return array
     */
    protected function createResources($filesystemPaths)
    {
        $resources = array();

        foreach ($filesystemPaths as $path => $filesystemPath) {
            $child = $this->createResource($filesystemPath);
            $child->attachTo($this, $path);

            $resources[] = $child;
        }

        return $resources;
    }
}
