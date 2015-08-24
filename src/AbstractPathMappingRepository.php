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

use Puli\Repository\Api\Resource\FilesystemResource;
use Puli\Repository\Api\ResourceCollection;
use Puli\Repository\Api\UnsupportedResourceException;
use Puli\Repository\Resource\DirectoryResource;
use Puli\Repository\Resource\FileResource;
use Puli\Repository\Resource\GenericResource;
use RuntimeException;
use Webmozart\KeyValueStore\Api\CountableStore;
use Webmozart\KeyValueStore\Api\KeyValueStore;
use Webmozart\KeyValueStore\Api\SortableStore;
use Webmozart\KeyValueStore\Decorator\CountableDecorator;
use Webmozart\KeyValueStore\Decorator\SortableDecorator;
use Webmozart\PathUtil\Path;

/**
 * Abstract base for Path mapping repositories.
 *
 * @since  1.0
 *
 * @author Bernhard Schussek <bschussek@gmail.com>
 * @author Titouan Galopin <galopintitouan@gmail.com>
 */
abstract class AbstractPathMappingRepository extends AbstractRepository
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
     * Add the resource (internal method after checks of add()).
     *
     * @param string             $path
     * @param FilesystemResource $resource
     */
    abstract protected function addResource($path, FilesystemResource $resource);

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

            $this->sortStore();

            return;
        }

        if ($resource instanceof FilesystemResource) {
            $this->ensureDirectoryExists(Path::getDirectory($path));
            $this->addResource($path, $resource);
            $this->sortStore();

            return;
        }

        throw new UnsupportedResourceException(sprintf(
            'The passed resource must be a FilesystemResource or a ResourceCollection. Got: %s',
            is_object($resource) ? get_class($resource) : gettype($resource)
        ));
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
        if (!$this->store instanceof CountableStore) {
            $this->store = new CountableDecorator($this->store);
        }

        return $this->store->count();
    }

    /**
     * Sort the store by keys.
     */
    protected function sortStore()
    {
        if (!$this->store instanceof SortableStore) {
            $this->store = new SortableDecorator($this->store);
        }

        $this->store->sort();
    }

    /**
     * Create a filesystem or generic resource.
     *
     * @param string $filesystemPath
     *
     * @return DirectoryResource|FileResource|GenericResource
     */
    protected function createResource($filesystemPath, $path = null)
    {
        if ($filesystemPath && file_exists($filesystemPath)) {
            return $this->createFilesystemResource($filesystemPath, $path);
        }

        return $this->createVirtualResource($path);
    }

    /**
     * Create a resource using its filesystem path.
     *
     * If the filesystem path is a directory, a DirectoryResource will be created.
     * If the filesystem path is a file, a FileResource will be created.
     * If the filesystem does not exists, a GenericResource will be created.
     *
     * @param string $filesystemPath The filesystem path.
     * @param string $path           The repository path.
     *
     * @return DirectoryResource|FileResource The created resource.
     *
     * @throws RuntimeException If the file / directory does not exist.
     */
    protected function createFilesystemResource($filesystemPath, $path = null)
    {
        $resource = null;

        if (is_dir($filesystemPath)) {
            $resource = new DirectoryResource($filesystemPath);
        } elseif (is_file($filesystemPath)) {
            $resource = new FileResource($filesystemPath);
        }

        if ($resource) {
            $resource->attachTo($this, $path);

            return $resource;
        }

        throw new RuntimeException(sprintf(
            'Trying to create a FilesystemResource on a non-existing file or directory "%s"',
            $filesystemPath
        ));
    }

    /**
     * @param string|null $path
     *
     * @return GenericResource
     */
    protected function createVirtualResource($path = null)
    {
        $resource = new GenericResource();
        $resource->attachTo($this, $path);

        return $resource;
    }
}
