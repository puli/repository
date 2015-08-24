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
use Iterator;
use Puli\Repository\Api\EditableRepository;
use Puli\Repository\Api\Resource\FilesystemResource;
use Puli\Repository\Api\Resource\Resource;
use Puli\Repository\Api\ResourceNotFoundException;
use Puli\Repository\Api\UnsupportedLanguageException;
use Puli\Repository\Resource\Collection\ArrayResourceCollection;
use Webmozart\Assert\Assert;
use Webmozart\Glob\Glob;
use Webmozart\Glob\Iterator\GlobFilterIterator;
use Webmozart\Glob\Iterator\RegexFilterIterator;

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
 *
 * @author Bernhard Schussek <bschussek@gmail.com>
 * @author Titouan Galopin <galopintitouan@gmail.com>
 */
class OptimizedPathMappingRepository extends AbstractPathMappingRepository implements EditableRepository
{
    /**
     * {@inheritdoc}
     */
    public function get($path)
    {
        $path = $this->sanitizePath($path);

        if (!$this->store->exists($path)) {
            throw ResourceNotFoundException::forPath($path);
        }

        return $this->createResource($this->store->get($path), $path);
    }

    /**
     * {@inheritdoc}
     */
    public function find($query, $language = 'glob')
    {
        $this->validateSearchLanguage($language);

        $query = $this->sanitizePath($query);
        $resources = new ArrayResourceCollection();

        if (Glob::isDynamic($query)) {
            $resources = $this->iteratorToCollection($this->getGlobIterator($query));
        } elseif ($this->store->exists($query)) {
            $resources = new ArrayResourceCollection(array(
                $this->createResource($this->store->get($query), $query),
            ));
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

        $query = $this->sanitizePath($query);

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
    public function remove($query, $language = 'glob')
    {
        $this->validateSearchLanguage($language);

        $query = $this->sanitizePath($query);

        Assert::notEmpty(trim($query, '/'), 'The root directory cannot be removed.');

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
    public function listChildren($path)
    {
        $iterator = $this->getChildIterator($this->get($path));

        return $this->iteratorToCollection($iterator);
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
     * @param string             $path
     * @param FilesystemResource $resource
     */
    protected function addResource($path, FilesystemResource $resource)
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
     * @return RegexFilterIterator The iterator of paths.
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
     * @return RegexFilterIterator The iterator of paths.
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
     * @return GlobFilterIterator The iterator of paths.
     */
    private function getGlobIterator($glob)
    {
        return new GlobFilterIterator(
            $glob,
            new ArrayIterator($this->store->keys())
        );
    }

    /**
     * Transform an iterator of paths into a collection of resources.
     *
     * @param Iterator $iterator
     *
     * @return ArrayResourceCollection
     */
    private function iteratorToCollection(Iterator $iterator)
    {
        $filesystemPaths = $this->store->getMultiple(iterator_to_array($iterator));
        $collection = new ArrayResourceCollection();

        foreach ($filesystemPaths as $path => $filesystemPath) {
            $collection->add($this->createResource($filesystemPath, $path));
        }

        return $collection;
    }
}
