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
use Puli\Repository\Api\Resource\PuliResource;
use Puli\Repository\Api\ResourceNotFoundException;
use Puli\Repository\Api\UnsupportedLanguageException;
use Puli\Repository\Resource\Collection\ArrayResourceCollection;
use Puli\Repository\Resource\LinkResource;
use Webmozart\Assert\Assert;
use Webmozart\Glob\Glob;
use Webmozart\Glob\Iterator\GlobFilterIterator;
use Webmozart\Glob\Iterator\RegexFilterIterator;
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
 *
 * @author Bernhard Schussek <bschussek@gmail.com>
 * @author Titouan Galopin <galopintitouan@gmail.com>
 */
class OptimizedJsonRepository extends AbstractJsonRepository implements EditableRepository
{
    /**
     * {@inheritdoc}
     */
    public function get($path)
    {
        if (null === $this->data) {
            $this->load();
        }

        $path = $this->sanitizePath($path);

        if (!array_key_exists($path, $this->data)) {
            throw ResourceNotFoundException::forPath($path);
        }

        $data = $this->data[$path];

        return $this->createResource(is_array($data) ? $data[count($data) - 1] : $data, $path);
    }

    /**
     * {@inheritdoc}
     */
    public function find($query, $language = 'glob')
    {
        if (null === $this->data) {
            $this->load();
        }

        $this->validateSearchLanguage($language);

        $query = $this->sanitizePath($query);
        $resources = new ArrayResourceCollection();

        if (Glob::isDynamic($query)) {
            $resources = $this->iteratorToCollection($this->getGlobIterator($query));
        } elseif (array_key_exists($query, $this->data)) {
            $resources = new ArrayResourceCollection(array(
                $this->createResource($this->data[$query], $query),
            ));
        }

        return $resources;
    }

    /**
     * {@inheritdoc}
     */
    public function contains($query, $language = 'glob')
    {
        if (null === $this->data) {
            $this->load();
        }

        if ('glob' !== $language) {
            throw UnsupportedLanguageException::forLanguage($language);
        }

        $query = $this->sanitizePath($query);

        if (Glob::isDynamic($query)) {
            $iterator = $this->getGlobIterator($query);
            $iterator->rewind();

            return $iterator->valid();
        }

        return array_key_exists($query, $this->data);
    }

    /**
     * {@inheritdoc}
     */
    public function remove($query, $language = 'glob')
    {
        if (null === $this->data) {
            $this->load();
        }

        $this->validateSearchLanguage($language);

        $query = $this->sanitizePath($query);

        Assert::notEmpty(trim($query, '/'), 'The root directory cannot be removed.');

        // Find resources to remove
        // (more efficient that find() as we do not need to unserialize them)
        $paths = array();

        if (Glob::isDynamic($query)) {
            $paths = $this->getGlobIterator($query);
        } elseif (array_key_exists($query, $this->data)) {
            $paths = array($query);
        }

        // Remove the resources found
        $nbOfResources = $this->countStore();

        foreach ($paths as $path) {
            $this->removePath($path);
        }

        $this->flush();

        return $nbOfResources - $this->countStore();
    }

    /**
     * {@inheritdoc}
     */
    public function listChildren($path)
    {
        if (null === $this->data) {
            $this->load();
        }

        $iterator = $this->getChildIterator($this->get($path));

        return $this->iteratorToCollection($iterator);
    }

    /**
     * {@inheritdoc}
     */
    public function hasChildren($path)
    {
        if (null === $this->data) {
            $this->load();
        }

        $iterator = $this->getChildIterator($this->get($path));
        $iterator->rewind();

        return $iterator->valid();
    }

    /**
     * @param string             $path
     * @param FilesystemResource $resource
     */
    protected function addFilesystemResource($path, FilesystemResource $resource)
    {
        // Read children before attaching the resource to this repository
        $children = $resource->listChildren();

        $resource->attachTo($this, $path);

        // Add the resource before adding its children, so that the array stays sorted
        $this->data[$path] = Path::makeRelative($resource->getFilesystemPath(), $this->baseDirectory);

        $basePath = '/' === $path ? $path : $path.'/';

        foreach ($children as $name => $child) {
            $this->addFilesystemResource($basePath.$name, $child);
        }
    }

    /**
     * {@inheritdoc}
     */
    protected function addLinkResource($path, LinkResource $resource)
    {
        $resource->attachTo($this, $path);
        $this->data[$path] = 'l:'.$resource->getTargetPath();
    }

    /**
     * @param string $path
     */
    private function removePath($path)
    {
        if (!array_key_exists($path, $this->data)) {
            return;
        }

        // Remove children first
        $children = $this->getRecursivePathChildIterator($path);

        foreach ($children as $child) {
            unset($this->data[$child]);
        }

        // Remove the resource
        unset($this->data[$path]);
    }

    /**
     * Returns an iterator for the children paths of a resource.
     *
     * @param PuliResource $resource The resource.
     *
     * @return RegexFilterIterator The iterator of paths.
     */
    private function getChildIterator(PuliResource $resource)
    {
        $staticPrefix = rtrim($resource->getPath(), '/').'/';
        $regExp = '~^'.preg_quote($staticPrefix, '~').'[^/]+$~';

        return new RegexFilterIterator(
            $regExp,
            $staticPrefix,
            new ArrayIterator(array_keys($this->data))
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
            new ArrayIterator(array_keys($this->data))
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
            new ArrayIterator(array_keys($this->data))
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
        $collection = new ArrayResourceCollection();

        foreach ($iterator as $path) {
            $collection->add($this->createResource($this->data[$path], $path));
        }

        return $collection;
    }
}
