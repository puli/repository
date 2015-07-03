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
use ArrayIterator;
use Iterator;

use Puli\Repository\Api\EditableRepository;
use Puli\Repository\Api\Resource\FilesystemResource;
use Puli\Repository\Api\Resource\Resource;
use Puli\Repository\Api\ResourceCollection;
use Puli\Repository\Api\ResourceNotFoundException;
use Puli\Repository\Api\UnsupportedLanguageException;
use Puli\Repository\Api\UnsupportedResourceException;
use Puli\Repository\Resource\Collection\ArrayResourceCollection;
use Puli\Repository\Resource\Collection\FilesystemResourceCollection;
use Puli\Repository\Resource\DirectoryResource;
use Puli\Repository\Resource\FileResource;
use Puli\Repository\Resource\GenericResource;

use Webmozart\Assert\Assert;
use Webmozart\Glob\Glob;
use Webmozart\Glob\Iterator\GlobFilterIterator;
use Webmozart\Glob\Iterator\RegexFilterIterator;
use Webmozart\KeyValueStore\Api\KeyValueStore;
use Webmozart\PathUtil\Path;

/**
 * An optimized path mapping resource repository.
 * When a resource is added, all its children are resolved
 * and getting them is much faster.
 *
 * Resources can be added with the method {@link add()}:
 *
 * ```php
 * use Puli\Repository\OptimizedPathMappingRepository;
 *
 * $repo = new OptimizedPathMappingRepository();
 * $repo->add('/css', new DirectoryResource('/path/to/project/res/css'));
 * ```
 *
 * This repository only supports instances of FilesystemResource.
 *
 * @since  1.0
 * @author Bernhard Schussek <bschussek@gmail.com>
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
    public function get($path)
    {
        Assert::stringNotEmpty($path, 'The path must be a non-empty string. Got: %s');
        Assert::startsWith($path, '/', 'The path %s is not absolute.');

        $path = Path::canonicalize($path);

        if (!$this->store->exists($path)) {
            throw ResourceNotFoundException::forPath($path);
        }

        $resource = $this->createResource($this->store->get($path));
        $resource->attachTo($this, $path);

        return $resource;
    }

    /**
     * {@inheritdoc}
     */
    public function find($query, $language = 'glob')
    {
        if ('glob' !== $language) {
            throw UnsupportedLanguageException::forLanguage($language);
        }

        Assert::stringNotEmpty($query, 'The glob must be a non-empty string. Got: %s');
        Assert::startsWith($query, '/', 'The glob %s is not absolute.');

        $query = Path::canonicalize($query);
        $resources = new ArrayResourceCollection();

        if (Glob::isDynamic($query)) {
            $resources = $this->iteratorToCollection($this->getGlobIterator($query));
        } elseif ($this->store->exists($query)) {
            $resource = $this->createResource($this->store->get($query));
            $resource->attachTo($this, $query);

            $resources = new ArrayResourceCollection(array($resource));
        }

        return $resources;
    }

    /**
     * {@inheritdoc}
     */
    public function contains($query, $language = 'glob')
    {
        if ('glob' !== $language) {
            throw UnsupportedLanguageException::forLanguage($language);
        }

        Assert::stringNotEmpty($query, 'The glob must be a non-empty string. Got: %s');
        Assert::startsWith($query, '/', 'The glob %s is not absolute.');

        $query = Path::canonicalize($query);

        if (Glob::isDynamic($query)) {
            $iterator = $this->getGlobIterator($query);
            $iterator->rewind();

            return $iterator->valid();
        }

        return $this->store->exists($query);
    }

    /**
     * {@inheritdoc}
     */
    public function add($path, $resource)
    {
        Assert::stringNotEmpty($path, 'The path must be a non-empty string. Got: %s');
        Assert::startsWith($path, '/', 'The path %s is not absolute.');

        $path = Path::canonicalize($path);

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
    public function remove($query, $language = 'glob')
    {
        if ('glob' !== $language) {
            throw UnsupportedLanguageException::forLanguage($language);
        }

        Assert::stringNotEmpty($query, 'The glob must be a non-empty string. Got: %s');
        Assert::startsWith($query, '/', 'The glob %s is not absolute.');

        $query = Path::canonicalize($query);

        Assert::notEq('', trim($query, '/'), 'The root directory cannot be removed.');

        // Find resources to remove
        // (more efficient that find() as we do not need to unserialize them)
        $paths = array();

        if (Glob::isDynamic($query)) {
            $paths = $this->getGlobIterator($query);
        } elseif ($this->store->exists($query)) {
            $paths = array($query);
        }

        // Remove the resources found
        $nbOfResources = $this->countStore();

        foreach ($paths as $path) {
            $this->removePath($path);
        }

        return $nbOfResources - $this->countStore();
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
     * {@inheritdoc}
     */
    public function listChildren($path)
    {
        $iterator = $this->getChildIterator($this->get($path));
        $children = $this->iteratorToCollection($iterator);

        return new ArrayResourceCollection($children);
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

        // Add the resource before adding its children, so that the array stays sorted
        $this->store->set($path, $resource->getFilesystemPath());

        $basePath = '/' === $path ? $path : $path.'/';

        foreach ($children as $name => $child) {
            $this->addResource($basePath.$name, $child);
        }
    }


    /**
     * @param string $path
     */
    private function removePath($path)
    {
        if (!$this->store->exists($path)) {
            return;
        }

        // Remove children first
        $children = $this->getRecursivePathChildIterator($path);

        foreach ($children as $child) {
            $this->store->remove($child);
        }

        // Remove the resource
        $this->store->remove($path);
    }

    /**
     * Returns an iterator for the children paths of a resource.
     *
     * @param Resource $resource The resource.
     *
     * @return RegexFilterIterator|string[] The iterator of paths.
     */
    private function getChildIterator(Resource $resource)
    {
        $staticPrefix = rtrim($resource->getPath(), '/').'/';
        $regExp = '~^'.preg_quote($staticPrefix, '~').'[^/]+$~';

        return new RegexFilterIterator(
            $regExp,
            $staticPrefix,
            new ArrayIterator($this->store->keys())
        );
    }

    /**
     * Returns a recursive iterator for the children paths under a given path.
     *
     * @param string $path The path.
     *
     * @return RegexFilterIterator|string[] The iterator of paths.
     */
    private function getRecursivePathChildIterator($path)
    {
        $staticPrefix = rtrim($path, '/').'/';
        $regExp = '~^'.preg_quote($staticPrefix, '~').'.+$~';

        return new RegexFilterIterator(
            $regExp,
            $staticPrefix,
            new ArrayIterator($this->store->keys())
        );
    }

    /**
     * Returns an iterator for a glob.
     *
     * @param string $glob The glob.
     *
     * @return GlobFilterIterator|string[] The iterator of paths.
     */
    private function getGlobIterator($glob)
    {
        return new GlobFilterIterator(
            $glob,
            new ArrayIterator($this->store->keys())
        );
    }

    /**
     * Recursively creates a directory for a path.
     *
     * @param string $path A directory path.
     * @return DirectoryResource The created resource
     */
    private function ensureDirectoryExists($path)
    {
        if (!$this->store->exists($path)) {
            // Recursively initialize parent directories
            if ($path !== '/') {
                $this->ensureDirectoryExists(Path::getDirectory($path));
            }

            $this->store->set($path, null);

            return;
        }
    }

    /**
     * Transform an iterator of paths into a collection of resources
     *
     * @param Iterator $iterator
     * @return ArrayResourceCollection
     */
    private function iteratorToCollection(Iterator $iterator)
    {
        $filesystemPaths = $this->store->getMultiple(iterator_to_array($iterator));
        $resources = array();

        foreach ($filesystemPaths as $path => $filesystemPath) {
            $resource = $this->createResource($filesystemPath);
            $resource->attachTo($this, $path);

            $resources[] = $resource;
        }

        return new ArrayResourceCollection($resources);
    }

    /**
     * Count the number of elements in the store
     */
    private function createRoot()
    {
        if ($this->store->exists('/')) {
            return;
        }

        $this->store->set('/', null);
    }

    /**
     * Count the number of elements in the store
     */
    private function countStore()
    {
        if ($this->store instanceof Countable) {
            return count($this->store);
        }

        return count($this->store->keys());
    }

    /**
     * Sort the store by keys
     */
    private function sortStore()
    {
        $resources = $this->store->getMultiple($this->store->keys());

        ksort($resources);

        $this->store->clear();

        foreach ($resources as $path => $resource) {
            $this->store->set($path, $resource);
        }
    }

    /**
     * Create a resource using its filesystem path
     *
     * @param string $filesystemPath
     * @return FilesystemResource
     */
    private function createResource($filesystemPath)
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
}
