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
use BadMethodCallException;
use FilesystemIterator;
use RecursiveDirectoryIterator;
use Puli\Repository\Api\EditableRepository;
use Puli\Repository\Api\Resource\FilesystemResource;
use Puli\Repository\Api\Resource\Resource;
use Puli\Repository\Api\ResourceCollection;
use Puli\Repository\Api\ResourceNotFoundException;
use Puli\Repository\Api\UnsupportedResourceException;
use Puli\Repository\Resource\Collection\ArrayResourceCollection;
use Puli\Repository\Resource\GenericResource;
use Webmozart\Glob\Glob;
use Webmozart\Glob\Iterator\RegexFilterIterator;
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
 *
 * @author Bernhard Schussek <bschussek@gmail.com>
 * @author Titouan Galopin <galopintitouan@gmail.com>
 */
class PathMappingRepository extends AbstractPathMappingRepository implements EditableRepository
{
    /**
     * {@inheritdoc}
     */
    public function get($path)
    {
        $path = $this->sanitizePath($path);
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
        $this->validateSearchLanguage($language);

        return $this->search($this->sanitizePath($query), false);
    }

    /**
     * {@inheritdoc}
     */
    public function contains($query, $language = 'glob')
    {
        $this->validateSearchLanguage($language);

        return $this->search($this->sanitizePath($query), true)->count() > 0;
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
     * @return int The number of resources removed from the repository.
     *
     * @throws BadMethodCallException
     */
    public function remove($query, $language = 'glob')
    {
        throw new BadMethodCallException(sprintf('%s\(\) is not supported in %s', __METHOD__, __CLASS__));
    }

    /**
     * {@inheritdoc}
     */
    public function listChildren($path)
    {
        return $this->getChildren($this->get($path));
    }

    /**
     * {@inheritdoc}
     */
    public function hasChildren($path)
    {
        return $this->getChildren($this->get($path))->count() !== 0;
    }

    /**
     * Find a resource by its path.
     *
     * @param string $path The path to resolve.
     *
     * @return GenericResource|null The resource or null if the resource is not found.
     */
    private function resolveResource($path)
    {
        /*
         * If the path exists in the store, return it directly
         */
        if ($this->store->exists($path)) {
            $resolved = $this->createResource($this->store->get($path));
            $resolved->attachTo($this, $path);

            return $resolved;
        }

        /*
         * Otherwise, we need to "resolve" it in two steps:
         *      1.  find the resources from the store that are potential parent
         *          of the path (we find them using regular expressions)
         *      2.  for each of these potential parent, we try to find a real
         *          file or directory on the filesystem and if we do find one,
         *          we stop
         */
        $basePaths = array_reverse($this->store->keys());

        foreach ($basePaths as $key => $basePath) {
            if (!Path::isBasePath($basePath, $path)) {
                continue;
            }

            $filesystemBasePath = rtrim($this->store->get($basePath), '/').'/';
            $filesystemPath = substr_replace($path, $filesystemBasePath, 0, strlen($basePath));

            $resource = $this->createResource($filesystemPath);

            if ($resource instanceof FilesystemResource) {
                $resource->attachTo($this, $path);

                return $resource;
            }
        }

        return null;
    }

    /**
     * Search for resources by querying their path.
     *
     * @param string $query        The glob query.
     * @param bool   $singleResult Should this method stop after finding a
     *                             first result, for performances.
     *
     * @return ArrayResourceCollection The results of search.
     */
    private function search($query, $singleResult = false)
    {
        $resources = new ArrayResourceCollection();

        /*
         * If the query is not a glob, return it directly
         */
        if (!Glob::isDynamic($query)) {
            $resource = $this->resolveResource($query);

            if ($resource instanceof Resource) {
                $resources->add($resource);
            }

            return $resources;
        }

        /*
         * Otherwise, we need to search for paths matching the glob:
         *      1.  find the root resource of all search results by
         *          resolving the glob base path
         *      2.  find all the children of this root resource and
         *          try to match their path to the query
         */
        $basePath = Glob::getBasePath($query);
        $baseResource = $this->resolveResource($basePath);

        if (null === $baseResource) {
            return $resources;
        }

        $children = $this->getChildrenRecursive($baseResource);

        $i = 0;
        $found = false;

        while ($i < $children->count() && !($found && $singleResult)) {
            /** @var Resource $child */
            $child = $children->get($i);

            $found = Glob::match($child->getRepositoryPath(), $query);

            if ($found) {
                $resources->add($child);
            }

            ++$i;
        }

        return $resources;
    }

    /**
     * Add a given resource to the repository.
     *
     * @param string             $path
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
     * Recursively find all the children of a given resource.
     *
     * @param Resource $resource The resource.
     *
     * @return ArrayResourceCollection The children.
     */
    private function getChildrenRecursive(Resource $resource)
    {
        $resources = new ArrayResourceCollection();

        foreach ($this->getChildren($resource) as $child) {
            $resources->merge($this->getChildrenRecursive($child)->toArray());
        }

        $resources->add($resource);

        return $resources;
    }

    /**
     * Find the direct children of a given resource.
     *
     * @param Resource $resource The resource.
     *
     * @return ArrayResourceCollection The children.
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
     * Find the direct children of a given filesystem resource.
     *
     * @param FilesystemResource $resource The resource.
     *
     * @return Resource[] The children.
     */
    private function getFilesystemResourceChildren(FilesystemResource $resource)
    {
        if (!is_dir($resource->getFilesystemPath())) {
            return array();
        }

        $iterator = new RecursiveDirectoryIterator(
            $resource->getFilesystemPath(),
            FilesystemIterator::CURRENT_AS_PATHNAME | FilesystemIterator::SKIP_DOTS
        );

        $filesystemPaths = iterator_to_array($iterator);

        // RecursiveDirectoryIterator is not guaranteed to return sorted results
        sort($filesystemPaths);

        $resources = array();

        foreach ($filesystemPaths as $filesystemPath) {
            $child = $this->createResource($filesystemPath);

            $path = preg_replace(
                '~^'.preg_quote(rtrim($resource->getFilesystemPath(), '/').'/', '~').'~',
                rtrim($resource->getRepositoryPath(), '/').'/',
                $filesystemPath
            );

            $child->attachTo($this, $path);

            $resources[] = $child;
        }

        return $resources;
    }

    /**
     * Find the direct children of a given virtual resource.
     *
     * @param Resource $resource The resource.
     *
     * @return Resource[] The children.
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

        return $this->createResources($filesystemPaths);
    }
}
