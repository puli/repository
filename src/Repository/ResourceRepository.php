<?php

/*
 * This file is part of the Puli package.
 *
 * (c) Bernhard Schussek <bschussek@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Webmozart\Puli\Repository;

use Webmozart\Puli\Filesystem\FilesystemLocator;
use Webmozart\Puli\Filesystem\Resource\LocalDirectoryResource;
use Webmozart\Puli\Locator\AbstractResourceLocator;
use Webmozart\Puli\Locator\ResourceLocatorInterface;
use Webmozart\Puli\Locator\ResourceNotFoundException;
use Webmozart\Puli\Path\Path;
use Webmozart\Puli\Pattern\PatternFactoryInterface;
use Webmozart\Puli\Pattern\PatternInterface;
use Webmozart\Puli\Resource\DirectoryResource;
use Webmozart\Puli\Resource\DirectoryResourceInterface;
use Webmozart\Puli\Resource\FileResourceInterface;
use Webmozart\Puli\Resource\NoDirectoryException;
use Webmozart\Puli\Resource\ResourceCollection;
use Webmozart\Puli\Resource\ResourceCollectionInterface;
use Webmozart\Puli\Resource\ResourceInterface;
use Webmozart\Puli\Resource\UnsupportedResourceException;

/**
 * @since  1.0
 * @author Bernhard Schussek <bschussek@gmail.com>
 */
class ResourceRepository extends AbstractResourceLocator implements ResourceRepositoryInterface
{
    /**
     * @var FileResourceInterface[]|DirectoryResourceInterface[]
     */
    private $resources = array();

    /**
     * @var \SplObjectStorage[]
     */
    private $resourcesByTag = array();

    /**
     * @var ResourceLocatorInterface
     */
    private $backend;

    public function __construct(ResourceLocatorInterface $backend = null, PatternFactoryInterface $patternFactory = null)
    {
        parent::__construct($patternFactory);

        $this->backend = $backend ?: new FilesystemLocator(null, $patternFactory);
        $this->resources['/'] = DirectoryResource::forPath('/');
    }

    public function getByTag($tag)
    {
        if (!isset($this->resourcesByTag[$tag])) {
            return new ResourceCollection();
        }

        return new ResourceCollection(iterator_to_array($this->resourcesByTag[$tag]));
    }

    public function add($path, $resource)
    {
        if ('' === $path) {
            throw new \InvalidArgumentException(
                'Please pass a non-empty selector.'
            );
        }

        $path = Path::canonicalize($path);

        if ('/' === $path) {
            throw new \InvalidArgumentException(
                'You cannot map the root directory "/".'
            );
        }

        if (is_string($resource)) {
            $resource = $this->backend->get($resource);
        }

        if ($resource instanceof ResourceCollectionInterface) {
            foreach ($resource as $entry) {
                /** @var ResourceInterface $entry */
                $this->copyToRepository($entry, $path.'/'.$entry->getName());
            }
        } else {
            $this->copyToRepository($resource, $path);
        }
    }

    public function remove($selector)
    {
        if ('' === $selector) {
            throw new \InvalidArgumentException(
                'Please pass a non-empty selector.'
            );
        }

        if (is_string($selector) && $this->patternFactory->acceptsSelector($selector)) {
            $selector = $this->patternFactory->createPattern($selector);
        }

        if ($selector instanceof PatternInterface) {
            $staticPrefix = $selector->getStaticPrefix();
            $regExp = $selector->getRegularExpression();

            foreach ($this->resources as $path => $resource) {
                // strpos() is slightly faster than substr() here
                if (0 !== strpos($path, $staticPrefix)) {
                    continue;
                }

                if (!preg_match($regExp, $path)) {
                    continue;
                }

                $this->removeFromRepository($resource);
            }

            return;
        }

        if (is_array($selector)) {
            foreach ($selector as $path) {
                $this->remove($path);
            }

            return;
        }

        $selector = Path::canonicalize($selector);

        if ('/' === $selector) {
            throw new UnsupportedOperationException(
                'The root directory "/" must not be removed.'
            );
        }

        if (isset($this->resources[$selector])) {
            $this->removeFromRepository($this->resources[$selector]);
        }
    }

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

        foreach ($resources as $resource) {
            /** @var \Webmozart\Puli\Resource\ResourceInterface $resource */
            $this->resourcesByTag[$tag]->attach($resource);
        }
    }

    public function untag($selector, $tag = null)
    {
        $resources = $this->find($selector);

        if (0 === count($resources)) {
            throw new ResourceNotFoundException(sprintf(
                'No resource was matched by the selector "%s".',
                $selector
            ));
        }

        if (null === $tag) {
            foreach ($resources as $resource) {
                $this->removeAllTagsFrom($resource);
            }
        } else {
            foreach ($resources as $resource) {
                $this->removeTagFrom($resource, $tag);
            }
        }

        $this->discardEmptyTags();
    }

    /**
     * {@inheritdoc}
     */
    public function getTags()
    {
        return array_keys($this->resourcesByTag);
    }

    protected function getImpl($path)
    {
        if (isset($this->resources[$path])) {
            return $this->resources[$path];
        }

        throw new ResourceNotFoundException(sprintf(
            'The resource "%s" does not exist.',
            $path
        ));
    }

    protected function findImpl(PatternInterface $pattern)
    {
        $staticPrefix = $pattern->getStaticPrefix();
        $regExp = $pattern->getRegularExpression();

        $resources = array();

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

        return new ResourceCollection($resources);
    }

    protected function containsImpl($path)
    {
        return isset($this->resources[$path]);
    }

    protected function containsPatternImpl(PatternInterface $pattern)
    {
        $staticPrefix = $pattern->getStaticPrefix();
        $regExp = $pattern->getRegularExpression();

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

    /**
     * @param string $repositoryPath
     *
     * @return LocalDirectoryResource
     *
     * @throws NoDirectoryException
     */
    private function getContainingDirectory($repositoryPath)
    {
        // Recursively initialize parent directories
        $parentPath = Path::getDirectory($repositoryPath);

        if (!isset($this->resources[$parentPath])) {
            $grandParent = $this->getContainingDirectory($parentPath);

            // Create new directory
            $this->resources[$parentPath] = DirectoryResource::forPath($parentPath);

            // Add as child node of the parent directory
            $grandParent->add($this->resources[$parentPath]);
        } elseif (!$this->resources[$parentPath] instanceof DirectoryResourceInterface) {
            throw new NoDirectoryException($parentPath);
        }

        return $this->resources[$parentPath];
    }

    private function removeTagFrom(ResourceInterface $resource, $tag)
    {
        if (!isset($this->resourcesByTag[$tag])) {
            return;
        }

        $this->resourcesByTag[$tag]->detach($resource);
    }

    private function removeAllTagsFrom(ResourceInterface $resource)
    {
        foreach ($this->resourcesByTag as $resources) {
            $resources->detach($resource);
        }
    }

    private function discardEmptyTags()
    {
        foreach ($this->resourcesByTag as $tag => $resources) {
            if (0 === count($resources)) {
                unset($this->resourcesByTag[$tag]);
            }
        }
    }

    /**
     * @param ResourceInterface $resource
     * @param string            $path
     *
     * @throws UnsupportedResourceException
     */
    private function copyToRepository(ResourceInterface $resource, $path)
    {
        if (isset($this->resources[$path])) {
            // If a resource with the same path was previously registered,
            // override it
            $resource = $resource->override($this->resources[$path]);
        } else {
            // Get a copy of the resource with the correct path
            $resource = $resource->copyTo($path);
        }

        // Create parent directory if needed
        // Create before adding the resource itself to keep the order
        $directory = $this->getContainingDirectory($path);

        // Add the resource
        $this->register($resource);
        $directory->add($resource);

        // Keep the resources sorted by file name
        ksort($this->resources);
    }

    private function removeFromRepository(ResourceInterface $resource)
    {
        // Detach resource from parent directory.
        // Doing so after removing the node itself ensures that this code is
        // not executed for the recursive child calls, because then their parent
        // node does not exist anymore.
        $parentPath = Path::getDirectory($resource->getPath());

        if (isset($this->resources[$parentPath])
                && $this->resources[$parentPath] instanceof DirectoryResourceInterface) {
            $this->resources[$parentPath]->remove($resource->getName());
        }

        $this->deregister($resource);

        $this->discardEmptyTags();
    }

    private function register(ResourceInterface $resource)
    {
        $this->resources[$resource->getPath()] = $resource;

        // Recursively register directory contents
        if ($resource instanceof DirectoryResourceInterface) {
            foreach ($resource->listEntries() as $entry) {
                $this->register($entry);
            }
        }
    }

    private function deregister(ResourceInterface $resource)
    {
        unset($this->resources[$resource->getPath()]);

        $this->removeAllTagsFrom($resource);

        // Recursively register directory contents
        if ($resource instanceof DirectoryResourceInterface) {
            foreach ($resource->listEntries() as $entry) {
                $this->deregister($entry);
            }
        }
    }
}
