<?php

/*
 * This file is part of the Puli package.
 *
 * (c) Bernhard Schussek <bschussek@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Webmozart\Puli;

use Webmozart\Puli\Filesystem\FilesystemRepository;
use Webmozart\Puli\Resource\AttachableResourceInterface;
use Webmozart\Puli\Resource\Collection\ResourceCollection;
use Webmozart\Puli\Resource\Collection\ResourceCollectionInterface;
use Webmozart\Puli\Resource\DirectoryResource;
use Webmozart\Puli\Resource\DirectoryResourceInterface;
use Webmozart\Puli\Resource\NoDirectoryException;
use Webmozart\Puli\Resource\ResourceInterface;
use Webmozart\Puli\Util\Path;
use Webmozart\Puli\Util\Selector;

/**
 * An in-memory resource repository.
 *
 * Resources can be added with the method {@link add}:
 *
 * ```php
 * use Webmozart\Puli\ResourceRepository;
 *
 * $repo = new ResourceRepository();
 * $repo->add('/css', new LocalDirectoryResource('/path/to/project/assets/css'));
 * ```
 *
 * Alternatively, another repository can be passed as "backend". The paths of
 * this backend can be passed to the second argument of {@link add}. By default,
 * a {@link FilesystemRepository} is used:
 *
 * ```php
 * use Webmozart\Puli\ResourceRepository;
 *
 * $repo = new ResourceRepository();
 * $repo->add('/css', '/path/to/project/assets/css');
 * ```
 *
 * You can also create the backend manually and pass it to the constructor:
 *
 * ```php
 * use Webmozart\Puli\Filesystem\FilesystemRepository;
 * use Webmozart\Puli\ResourceRepository;
 *
 * $backend = new FilesystemRepository('/path/to/project');
 *
 * $repo = new ResourceRepository($backend)
 * $repo->add('/css', '/assets/css');
 * ```
 *
 * @since  1.0
 * @author Bernhard Schussek <bschussek@gmail.com>
 */
class ResourceRepository implements ManageableRepositoryInterface
{
    /**
     * @var AttachableResourceInterface[]|DirectoryResourceInterface[]
     */
    private $resources = array();

    /**
     * @var \SplObjectStorage[]
     */
    private $resourcesByTag = array();

    /**
     * @var ResourceRepositoryInterface
     */
    private $backend;

    /**
     * Creates a new repository.
     *
     * The backend repository is used to lookup the paths passed to the
     * second argument of {@link add}. If none is passed, a
     * {@link FilesystemRepository} will be used.
     *
     * @param ResourceRepositoryInterface $backend The backend repository.
     *
     * @see ResourceRepository
     */
    public function __construct(ResourceRepositoryInterface $backend = null)
    {
        $this->backend = $backend ?: new FilesystemRepository();
        $this->resources['/'] = DirectoryResource::createAttached($this, '/');
    }

    /**
     * {@inheritdoc}
     */
    public function get($path)
    {
        if (isset($path[0]) && '/' !== $path[0]) {
            throw new InvalidPathException(sprintf(
                'The path "%s" is not absolute.',
                $path
            ));
        }

        $path = Path::canonicalize($path);

        if (!isset($this->resources[$path])) {
            throw new ResourceNotFoundException(sprintf(
                'The resource "%s" does not exist.',
                $path
            ));
        }

        return $this->resources[$path];
    }

    /**
     * {@inheritdoc}
     */
    public function find($selector)
    {
        if (isset($selector[0]) && '/' !== $selector[0]) {
            throw new InvalidPathException(sprintf(
                'The path "%s" is not absolute.',
                $selector
            ));
        }

        $selector = Path::canonicalize($selector);
        $staticPrefix = Selector::getStaticPrefix($selector);
        $resources = array();

        if (strlen($selector) > strlen($staticPrefix)) {
            $regExp = Selector::toRegEx($selector);

            foreach ($this->resources as $path => $resource) {
                // strpos() is slightly faster than substr() here
                if (0 !== strpos($path, $staticPrefix)) {
                    continue;
                }

                if (!preg_match($regExp, $path)) {
                    continue;
                }

                $resources[] = $resource;
            }
        } elseif (isset($this->resources[$selector])) {
            $resources[] = $this->resources[$selector];
        }

        return new ResourceCollection($resources);
    }

    /**
     * {@inheritdoc}
     */
    public function contains($selector)
    {
        if (isset($selector[0]) && '/' !== $selector[0]) {
            throw new InvalidPathException(sprintf(
                'The path "%s" is not absolute.',
                $selector
            ));
        }

        $selector = Path::canonicalize($selector);
        $staticPrefix = Selector::getStaticPrefix($selector);

        if (strlen($selector) > strlen($staticPrefix)) {
            $regExp = Selector::toRegEx($selector);

            foreach ($this->resources as $path => $resource) {
                // strpos() is slightly faster than substr() here
                if (0 !== strpos($path, $staticPrefix)) {
                    continue;
                }

                if (!preg_match($regExp, $path)) {
                    continue;
                }

                return true;
            }

            return false;
        }

        return isset($this->resources[$selector]);
    }

    /**
     * {@inheritdoc}
     *
     * If a path is passed as second argument, the added resources are fetched
     * from the backend passed to {@link __construct}.
     *
     * @param string                                                         $path     The path at which to add the resource.
     * @param string|AttachableResourceInterface|ResourceCollectionInterface $resource The resource(s) to add at that path.
     *
     * @throws InvalidPathException If the path is invalid.
     */
    public function add($path, $resource)
    {
        if ('' === $path) {
            throw new InvalidPathException(
                'Please pass a non-empty selector.'
            );
        }

        $path = Path::canonicalize($path);

        if (isset($path[0]) && '/' !== $path[0]) {
            throw new InvalidPathException(sprintf(
                'The path "%s" is not absolute.',
                $path
            ));
        }

        if (is_string($resource)) {
            $collection = $this->backend->find($resource);
            if (1 === count($collection)) {
                $resource = clone $collection[0];
            } else {
                foreach ($collection as $key => $entry) {
                    $collection[$key] = clone $entry;
                }
                $resource = $collection;
            }
        }

        if ($resource instanceof ResourceCollectionInterface) {
            foreach ($resource as $entry) {
                /** @var ResourceInterface $entry */
                $this->attachResource($entry, $path.'/'.$entry->getName());
            }
        } else {
            $this->attachResource($resource, $path);
        }
    }

    /**
     * {@inheritdoc}
     */
    public function remove($selector)
    {
        if ('' === $selector) {
            throw new InvalidPathException(
                'Please pass a non-empty selector.'
            );
        }

        $selector = Path::canonicalize($selector);

        if (isset($selector[0]) && '/' !== $selector[0]) {
            throw new InvalidPathException(sprintf(
                'The path "%s" is not absolute.',
                $selector
            ));
        }

        $staticPrefix = Selector::getStaticPrefix($selector);
        $removed = 0;

        // Is there a dynamic part ("*") in the selector?
        if (strlen($selector) > strlen($staticPrefix)) {
            $regExp = Selector::toRegEx($selector);

            foreach ($this->resources as $path => $resource) {
                // strpos() is slightly faster than substr() here
                if (0 !== strpos($path, $staticPrefix)) {
                    continue;
                }

                if (!preg_match($regExp, $path)) {
                    continue;
                }

                $this->detachResource($resource, $removed);
            }

            return $removed;
        }

        if (isset($this->resources[$selector])) {
            $this->detachResource($this->resources[$selector], $removed);
        }

        return $removed;
    }

    /**
     * {@inheritdoc}
     */
    public function tag($selector, $tag)
    {
        $resources = $this->find($selector);

        if (0 === count($resources)) {
            throw new ResourceNotFoundException(sprintf(
                'No resource was matched by the selector "%s".',
                $selector
            ));
        }

        if (!isset($this->resourcesByTag[$tag])) {
            $this->resourcesByTag[$tag] = new \SplObjectStorage();

            // Maintain order
            ksort($this->resourcesByTag);
        }

        $tagged = 0;

        foreach ($resources as $resource) {
            if (!$this->resourcesByTag[$tag]->contains($resource)) {
                $this->resourcesByTag[$tag]->attach($resource);
                ++$tagged;
            }
        }

        return $tagged;
    }

    /**
     * {@inheritdoc}
     */
    public function untag($selector, $tag = null)
    {
        $resources = $this->find($selector);

        if (0 === count($resources)) {
            throw new ResourceNotFoundException(sprintf(
                'No resource was matched by the selector "%s".',
                $selector
            ));
        }

        $untagged = 0;

        if (null === $tag) {
            foreach ($resources as $resource) {
                if ($this->removeAllTagsFrom($resource)) {
                    ++$untagged;
                }
            }
        } else {
            foreach ($resources as $resource) {
                if ($this->removeTagFrom($resource, $tag)) {
                    ++$untagged;
                }
            }
        }

        $this->discardEmptyTags();

        return $untagged;
    }

    /**
     * {@inheritdoc}
     */
    public function getByTag($tag)
    {
        if (!isset($this->resourcesByTag[$tag])) {
            return new ResourceCollection();
        }

        return new ResourceCollection(iterator_to_array($this->resourcesByTag[$tag]));
    }

    /**
     * {@inheritdoc}
     */
    public function getTags()
    {
        return array_keys($this->resourcesByTag);
    }

    /**
     * Recursively creates the base directories of a path.
     *
     * @param string $path A repository path.
     *
     * @throws NoDirectoryException If a resource with one of the base paths
     *                              exists, but is no directory.
     */
    private function initContainingDirectories($path)
    {
        if ('/' === $path) {
            return;
        }

        $parentPath = Path::getDirectory($path);

        // Relative paths don't have parent directories
        if ('' === $parentPath) {
            return;
        }

        if (!isset($this->resources[$parentPath])) {
            // Recursively initialize parent directories
            $this->initContainingDirectories($parentPath);
            $this->resources[$parentPath] = DirectoryResource::createAttached($this, $parentPath);

            return;
        }

        if (!$this->resources[$parentPath] instanceof DirectoryResourceInterface) {
            throw new NoDirectoryException($parentPath);
        }
    }

    /**
     * Removes a tag from the given resource.
     *
     * @param ResourceInterface $resource A resource.
     * @param string            $tag      The tag to remove.
     *
     * @return bool Whether any tag was removed.
     */
    private function removeTagFrom(ResourceInterface $resource, $tag)
    {
        if (!isset($this->resourcesByTag[$tag]) || !$this->resourcesByTag[$tag]->contains($resource)) {
            return false;
        }

        $this->resourcesByTag[$tag]->detach($resource);

        return true;
    }

    /**
     * Removes all tags from the given resource.
     *
     * @param ResourceInterface $resource A resource.
     *
     * @return bool Whether any tag was removed.
     */
    private function removeAllTagsFrom(ResourceInterface $resource)
    {
        $removed = false;

        foreach ($this->resourcesByTag as $resources) {
            if ($resources->contains($resource)) {
                $resources->detach($resource);
                $removed = true;
            }
        }

        return $removed;
    }

    /**
     * Removes empty tag containers from memory.
     *
     * Should be called after removing tags from resources.
     */
    private function discardEmptyTags()
    {
        foreach ($this->resourcesByTag as $tag => $resources) {
            if (0 === count($resources)) {
                unset($this->resourcesByTag[$tag]);
            }
        }
    }

    /**
     * Recursively attaches a resource to the repository.
     *
     * @param AttachableResourceInterface $resource The resource to attach.
     * @param string                      $path     The path at which to add
     *                                              the resource.
     */
    private function attachResource(AttachableResourceInterface $resource, $path)
    {
        $this->initContainingDirectories($path);

        // Add the resource
        $this->attachRecursively($resource, $path);

        // Keep the resources sorted by file name
        ksort($this->resources);
    }

    /**
     * Recursively detaches a resource from the repository.
     *
     * @param AttachableResourceInterface $resource The resource to detach.
     * @param integer                     $counter  Counts the number of detached
     *                                              resources.
     */
    private function detachResource(AttachableResourceInterface $resource, &$counter)
    {
        $this->detachRecursively($resource, $counter);

        $this->discardEmptyTags();
    }

    private function attachRecursively(AttachableResourceInterface $resource, $path)
    {
        if (isset($this->resources[$path])) {
            // If a resource with the same path was previously registered,
            // override it
            $resource->override($this->resources[$path]);
        }

        // Add the resource before adding nested resources, so that the
        // array stays sorted
        $this->resources[$path] = $resource;

        $basePath = '/' === $path ? $path : $path.'/';

        // Recursively attach directory contents
        if ($resource instanceof DirectoryResourceInterface) {
            foreach ($resource->listEntries() as $name => $entry) {
                $this->attachRecursively($entry, $basePath.$name);
            }
        }

        // Attach resource to locator *after* calling listEntries() and
        // override(), because these methods may depend on the previously
        // attached repository
        $resource->attachTo($this, $path);
    }

    private function detachRecursively(AttachableResourceInterface $resource, &$counter)
    {
        // Recursively register directory contents
        if ($resource instanceof DirectoryResourceInterface) {
            foreach ($this->find($resource->getPath().'/*') as $entry) {
                $this->detachRecursively($entry, $counter);
            }
        }

        unset($this->resources[$resource->getPath()]);

        $this->removeAllTagsFrom($resource);

        // Detach from locator
        $resource->detach($this);

        // Keep track of the number of removed resources
        ++$counter;
    }
}
