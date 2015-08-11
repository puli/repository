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
use Puli\Repository\Api\EditableRepository;
use Puli\Repository\Api\Resource\FilesystemResource;
use Puli\Repository\Api\Resource\Resource;
use Puli\Repository\Api\ResourceNotFoundException;
use Puli\Repository\Resource\Collection\ArrayResourceCollection;
use Puli\Repository\Resource\GenericResource;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
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
     * Not supported.
     *
     * {@inheritdoc}
     */
    public function remove($query, $language = 'glob')
    {
        throw new BadMethodCallException(sprintf('remove\(\) is not supported in %s', __CLASS__));
    }

    /**
     * {@inheritdoc}
     */
    public function listChildren($path)
    {
        return $this->getChildren($this->get($path), false);
    }

    /**
     * {@inheritdoc}
     */
    public function hasChildren($path)
    {
        return $this->getChildren($this->get($path), false)->count() !== 0;
    }

    /**
     * Add a given resource to the repository.
     *
     * @param string             $path
     * @param FilesystemResource $resource
     */
    protected function addResource($path, FilesystemResource $resource)
    {
        // Don't modify resources attached to other repositories
        if ($resource->isAttached()) {
            $resource = clone $resource;
        }

        $resource->attachTo($this, $path);

        $this->store->set($path, $resource->getFilesystemPath());
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
            return $this->createAndAttachResource($this->store->get($path), $path);
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

            if (file_exists($filesystemPath)) {
                return $this->createAndAttachResource($filesystemPath, $path);
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

        $children = $this->getChildren($baseResource, true);

        foreach ($children as $child) {
            if (Glob::match($child->getRepositoryPath(), $query)) {
                $resources->add($child);

                if ($singleResult) {
                    return $resources;
                }
            }
        }

        return $resources;
    }

    /**
     * Find the children of a given resource.
     *
     * @param Resource $resource  The resource.
     * @param bool     $recursive Should the method do a recusrive listing?
     *
     * @return ArrayResourceCollection The children.
     */
    private function getChildren(Resource $resource, $recursive = false)
    {
        if ($resource instanceof FilesystemResource) {
            return $this->getFilesystemResourceChildren($resource, $recursive);
        }

        if (!$recursive) {
            return $this->getVirtualResourceChildren($resource);
        }

        $resources = new ArrayResourceCollection();

        foreach ($this->getChildren($resource, false) as $child) {
            $resources->merge($this->getChildren($child, true)->toArray());
        }

        $resources->add($resource);

        return $resources;
    }

    /**
     * Find the direct children of a given virtual resource.
     *
     * @param Resource $resource The resource.
     *
     * @return ArrayResourceCollection The children.
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

        return new ArrayResourceCollection($this->createResources($filesystemPaths));
    }

    /**
     * Find the children of a given filesystem resource.
     *
     * @param FilesystemResource $resource  The resource.
     * @param bool               $recursive Should the method do a recusrive listing?
     *
     * @return ArrayResourceCollection The children.
     */
    private function getFilesystemResourceChildren(FilesystemResource $resource, $recursive = false)
    {
        if (!is_dir($resource->getFilesystemPath())) {
            return new ArrayResourceCollection();
        }

        if ($recursive) {
            $iterator = new RecursiveIteratorIterator(
                new RecursiveDirectoryIterator(
                    $resource->getFilesystemPath(),
                    FilesystemIterator::KEY_AS_PATHNAME
                    | FilesystemIterator::CURRENT_AS_FILEINFO
                    | FilesystemIterator::SKIP_DOTS
                ),
                RecursiveIteratorIterator::SELF_FIRST
            );
        } else {
            $iterator = new RecursiveDirectoryIterator(
                $resource->getFilesystemPath(),
                FilesystemIterator::KEY_AS_PATHNAME
                | FilesystemIterator::CURRENT_AS_FILEINFO
                | FilesystemIterator::SKIP_DOTS
            );
        }

        $filesystemPaths = array_keys(iterator_to_array($iterator));

        // RecursiveDirectoryIterator is not guaranteed to return sorted results
        sort($filesystemPaths);

        $resources = new ArrayResourceCollection();

        foreach ($filesystemPaths as $filesystemPath) {
            $path = preg_replace(
                '~^'.preg_quote(rtrim($resource->getFilesystemPath(), '/').'/', '~').'~',
                rtrim($resource->getRepositoryPath(), '/').'/',
                $filesystemPath
            );

            $resources->add($this->createAndAttachResource($filesystemPath, $path));
        }

        return $resources;
    }
}
