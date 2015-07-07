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
use BadMethodCallException;

use Puli\Repository\Api\EditableRepository;
use Puli\Repository\Api\Resource\FilesystemResource;
use Puli\Repository\Api\Resource\Resource;
use Puli\Repository\Api\ResourceCollection;
use Puli\Repository\Api\ResourceNotFoundException;
use Puli\Repository\Api\UnsupportedLanguageException;
use Puli\Repository\Api\UnsupportedResourceException;
use Puli\Repository\Resource\Collection\ArrayResourceCollection;
use Puli\Repository\Resource\DirectoryResource;
use Puli\Repository\Resource\FileResource;
use Puli\Repository\Resource\GenericResource;

use Webmozart\Assert\Assert;
use Webmozart\Glob\Glob;
use Webmozart\Glob\Iterator\RegexFilterIterator;
use Webmozart\KeyValueStore\Api\KeyValueStore;
use Webmozart\PathUtil\Path;

/**
 * A development path mapping resource repository.
 * Each resource is resolved at `get()` time to improve
 * developer experience.
 *
 * Resources can be added with the method {@link add()}:
 *
 * ```php
 * use Puli\Repository\PathMappingRepository;
 *
 * $repo = new PathMappingRepository();
 * $repo->add('/css', new DirectoryResource('/path/to/project/res/css'));
 * ```
 *
 * This repository only supports instances of FilesystemResource.
 *
 * @since  1.0
 * @author Bernhard Schussek <bschussek@gmail.com>
 * @author Titouan Galopin <galopintitouan@gmail.com>
 */
class PathMappingRepository implements EditableRepository
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
        $resource = $this->resolveResource($path);

        if (!$resource) {
            throw ResourceNotFoundException::forPath($path);
        }

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

        return $this->search($query, false);
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

        return $this->search($query, true)->count() > 0;
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
     * Removes all resources matching the given query.
     *
     * This method is not supported by this repository.
     *
     * @param string $query    A resource query.
     * @param string $language The language of the query. All implementations
     *                         must support the language "glob".
     *
     * @return integer The number of resources removed from the repository.
     *
     * @throws BadMethodCallException
     */
    public function remove($query, $language = 'glob')
    {
        throw new BadMethodCallException(sprintf('%s() is not supported in %s', __METHOD__, __CLASS__));
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
        return new ArrayResourceCollection($this->getChildren($this->get($path)));
    }

    /**
     * {@inheritdoc}
     */
    public function hasChildren($path)
    {
        return $this->getChildren($this->get($path))->count() !== 0;
    }

    /**
     * Find a resource on fetch time ("resolve the resource").
     *
     * @param string $path The path to resolve
     * @return \Puli\Repository\Api\Resource\Resource|null The resource or null if the resource is not found
     */
    private function resolveResource($path)
    {
        if ($this->store->exists($path)) {
            $resolved = $this->createResource($this->store->get($path));
            $resolved->attachTo($this, $path);

            return $resolved;
        }

        $storePaths = $this->store->keys();

        // Paths are sorted by length:
        // we need to reverse the order to be more efficient
        $storePaths = array_reverse($storePaths);

        // Create regex
        $regExpressions = array();

        foreach ($storePaths as $storePath) {
            $prefix = rtrim($storePath, '/').'/';
            $regExpressions[] = '~^'.preg_quote($prefix, '~').'~';
        }

        // Resolve the resource using paths and children
        $resolved = null;

        foreach ($regExpressions as $key => $regExpression) {
            if (preg_match($regExpression, $path)) {
                // The path match, try to find if the file exists here
                $filesystemRoot = rtrim($this->store->get($storePaths[$key]), '/') . '/';
                $filesystemPath = preg_replace($regExpression, $filesystemRoot, $path);

                $resource = $this->createResource($filesystemPath);

                if ($resource instanceof FilesystemResource) {
                    $resolved = $resource;
                    $resolved->attachTo($this, $path);
                    break;
                }
            }
        }

        return $resolved;
    }

    /**
     * Resolve a given glob on the repository.
     *
     * @param string $query The glob query.
     * @param bool $singleResult Should this method stop after finding a first result
     * @return ArrayResourceCollection The results of search.
     */
    private function search($query, $singleResult = false)
    {
        $resources = new ArrayResourceCollection();

        if (Glob::isDynamic($query)) {
            $basePath = Glob::getBasePath($query);
            $baseResource = $this->resolveResource($basePath);

            if ($baseResource !== null) {
                $children = $this->getChildrenRecursive($baseResource);

                foreach ($children as $child) {
                    if (Glob::match($child->getRepositoryPath(), $query)) {
                        $resources->add($child);

                        if ($singleResult) {
                            break;
                        }
                    }
                }
            }
        } else {
            $resource = $this->resolveResource($query);

            if ($resource instanceof Resource) {
                $resources->add($resource);
            }
        }

        return $resources;
    }

    /**
     * Recursively creates a directory for a path.
     *
     * @param string $path A directory path.
     * @return DirectoryResource The created resource
     */
    private function ensureDirectoryExists($path)
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
     * Add a given resource to the repository.
     *
     * @param string $path
     * @param FilesystemResource $resource
     */
    private function addResource($path, FilesystemResource $resource)
    {
        // Don't modify resources attached to other repositories
        if ($resource->isAttached()) {
            $resource = clone $resource;
        }

        $resource->attachTo($this, $path);

        $this->store->set($path, $resource->getFilesystemPath());
    }

    /**
     * Return the children of a given resource (explore recursively).
     *
     * @param \Puli\Repository\Api\Resource\Resource $resource The resource.
     * @return ArrayResourceCollection|\Puli\Repository\Api\Resource\Resource[]
     */
    private function getChildrenRecursive(Resource $resource)
    {
        $resources = new ArrayResourceCollection();

        foreach ($this->getChildren($resource) as $child) {
            $resources->merge($this->getChildrenRecursive($child));
        }

        $resources->add($resource);

        return $resources;
    }

    /**
     * Return the children of a given resource.
     *
     * @param \Puli\Repository\Api\Resource\Resource $resource The resource.
     * @return ArrayResourceCollection
     */
    private function getChildren(Resource $resource)
    {
        if ($resource instanceof FilesystemResource) {
            $children = $this->getFilesystemResourceChildren($resource);
        } else {
            $children = $this->getVirtualResourceChildren($resource);
        }

        return new ArrayResourceCollection($children);
    }

    /**
     * Return the children of a given filesystem resource.
     *
     * @param FilesystemResource $root
     * @return \Puli\Repository\Api\Resource\Resource[]
     */
    private function getFilesystemResourceChildren(FilesystemResource $root)
    {
        if (!is_dir($root->getFilesystemPath())) {
            return array();
        }

        $iterator = new \RecursiveDirectoryIterator(
            $root->getFilesystemPath(),
            \FilesystemIterator::CURRENT_AS_PATHNAME | \FilesystemIterator::SKIP_DOTS
        );

        $filesystemPaths = iterator_to_array($iterator);

        // RecursiveDirectoryIterator is not guaranteed to return sorted results
        sort($filesystemPaths);

        $resources = array();

        foreach ($filesystemPaths as $filesystemPath) {
            $resource = $this->createResource($filesystemPath);

            $path = preg_replace(
                '~^'.preg_quote(rtrim($root->getFilesystemPath(), '/').'/', '~').'~',
                rtrim($root->getRepositoryPath(), '/') . '/',
                $filesystemPath
            );

            $resource->attachTo($this, $path);

            $resources[] = $resource;
        }

        return $resources;
    }

    /**
     * Return the children of a given virtual resource.
     *
     * @param \Puli\Repository\Api\Resource\Resource $resource The resource.
     * @return \Puli\Repository\Api\Resource\Resource[]
     */
    private function getVirtualResourceChildren(Resource $resource)
    {
        $staticPrefix = rtrim($resource->getPath(), '/').'/';
        $regExp = '~^'.preg_quote($staticPrefix, '~').'[^/]+$~';

        $iterator = new RegexFilterIterator(
            $regExp,
            $staticPrefix,
            new ArrayIterator($this->store->keys())
        );

        // Sorted on adding
        $filesystemPaths = $this->store->getMultiple(iterator_to_array($iterator));

        $resources = array();

        foreach ($filesystemPaths as $path => $filesystemPath) {
            $resource = $this->createResource($filesystemPath);
            $resource->attachTo($this, $path);

            $resources[] = $resource;
        }

        return $resources;
    }

    /**
     * Create the repository root
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
     *
     * @return int
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
