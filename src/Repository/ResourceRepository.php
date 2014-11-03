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
use Webmozart\Puli\Locator\AbstractResourceLocator;
use Webmozart\Puli\Locator\ResourceLocatorInterface;
use Webmozart\Puli\Locator\ResourceNotFoundException;
use Webmozart\Puli\Path\Path;
use Webmozart\Puli\Pattern\PatternFactoryInterface;
use Webmozart\Puli\Pattern\PatternInterface;
use Webmozart\Puli\Resource\DirectoryResource;
use Webmozart\Puli\Resource\DirectoryResourceInterface;
use Webmozart\Puli\Resource\FileResource;
use Webmozart\Puli\Resource\ResourceCollection;
use Webmozart\Puli\Resource\ResourceCollectionInterface;
use Webmozart\Puli\Resource\ResourceInterface;

/**
 * @since  1.0
 * @author Bernhard Schussek <bschussek@gmail.com>
 */
class ResourceRepository extends AbstractResourceLocator implements ResourceRepositoryInterface
{
    /**
     * @var FileResource[]|DirectoryResource[]
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
        $this->resources['/'] = new DirectoryResource('/', null);
    }

    public function getByTag($tag)
    {
        if (!isset($this->resourcesByTag[$tag])) {
            return new ResourceCollection();
        }

        return new ResourceCollection(iterator_to_array($this->resourcesByTag[$tag]));
    }

    public function add($path, $selector)
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

        $resource = $this->backend->get($selector);

        if ($resource instanceof ResourceCollectionInterface) {
            foreach ($resource as $entry) {
                /** @var ResourceInterface $entry */
                $this->addResource($path.'/'.$entry->getName(), $entry);
            }
        } else {
            $this->addResource($path, $resource);
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

                $this->removeNode($path);
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
            $this->removeNode($selector);
        }
    }

    public function tag($selector, $tag)
    {
        $resources = $this->get($selector);

        if (!$resources instanceof ResourceCollectionInterface) {
            $resources = array($resources);
        }

        if (!isset($this->resourcesByTag[$tag])) {
            $this->resourcesByTag[$tag] = new \SplObjectStorage();

            // Maintain order
            ksort($this->resourcesByTag);
        }

        foreach ($resources as $resource) {
            /** @var \Webmozart\Puli\Resource\ResourceInterface $resource */
            $this->resourcesByTag[$tag]->attach($resource);
            $resource->addTag($tag);
        }
    }

    public function untag($selector, $tag = null)
    {
        $resources = $this->get($selector);

        if (!$resources instanceof ResourceCollectionInterface) {
            $resources = array($resources);
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

    protected function getImpl($repositoryPath)
    {
        if (isset($this->resources[$repositoryPath])) {
            return $this->resources[$repositoryPath];
        }

        throw new ResourceNotFoundException(sprintf(
            'The resource "%s" does not exist.',
            $repositoryPath
        ));
    }

    protected function getPatternImpl(PatternInterface $pattern)
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

    protected function containsImpl($repositoryPath)
    {
        return isset($this->resources[$repositoryPath]);
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

    private function removeNode($repositoryPath)
    {
        $resource = $this->resources[$repositoryPath];

        // Remove the resource
        unset($this->resources[$repositoryPath]);

        // Detach resource from parent directory.
        // Doing so after removing the node itself ensures that this code is
        // not executed for the recursive child calls, because then their parent
        // node does not exist anymore.
        $parentPath = Path::getDirectory($repositoryPath);

        if (isset($this->resources[$parentPath])
                && $this->resources[$parentPath] instanceof DirectoryResourceInterface) {
            $this->resources[$parentPath]->remove($resource->getName());
        }

        // Recursively remove all children
        if ($resource instanceof DirectoryResource) {
            foreach ($resource as $entry) {
                /** @var ResourceInterface $entry */
                $this->removeNode($entry->getPath());
            }
        }

        $this->removeAllTagsFrom($resource);
        $this->discardEmptyTags();
    }

    /**
     * @param string $repositoryPath
     *
     * @return DirectoryResource
     *
     * @throws NoDirectoryException
     */
    private function getOrCreateDirectoryOf($repositoryPath)
    {
        // Recursively initialize parent directories
        $parentPath = Path::getDirectory($repositoryPath);

        if (!isset($this->resources[$parentPath])) {
            $grandParent = $this->getOrCreateDirectoryOf($parentPath);

            // Create new directory
            $this->resources[$parentPath] = new DirectoryResource($parentPath, null);

            // Add as child node of the parent directory
            $grandParent->add($this->resources[$parentPath]);
        } elseif (!$this->resources[$parentPath] instanceof DirectoryResourceInterface) {
            throw new NoDirectoryException($parentPath);
        }

        return $this->resources[$parentPath];
    }

    private function removeTagFrom(ResourceInterface $resource, $tag)
    {
        $resource->removeTag($tag);

        if (!isset($this->resourcesByTag[$tag])) {
            return;
        }

        $this->resourcesByTag[$tag]->detach($resource);
    }

    private function removeAllTagsFrom(ResourceInterface $resource)
    {
        foreach ($resource->getTags() as $tag) {
            $this->removeTagFrom($resource, $tag);
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
     * @param string            $path
     * @param ResourceInterface $resource
     */
    private function addResource($path, ResourceInterface $resource)
    {
        $isDirectory = $resource instanceof DirectoryResourceInterface;

        // Create new Resource instances if necessary
        if (!isset($this->resources[$path])) {
            // Create parent directory if needed
            // Create before adding the resource itself to keep the order
            $directory = $this->getOrCreateDirectoryOf($path);

            // Add resource after the directory to maintain the correct order
            $this->resources[$path] = $isDirectory
                ? new DirectoryResource(
                    $path,
                    $resource->getRealPath()
                )
                : new FileResource(
                    $path,
                    $resource->getRealPath()
                );

            // Add the new node to the parent directory
            $directory->add($this->resources[$path]);

            // Keep the resources sorted by file name. This could probably be
            // optimized by inserting at the right position instead of
            // rearranging the complete array on every add.
            ksort($this->resources);
        } else {
            $this->resources[$path]->overridePath($resource->getRealPath());
        }

        // Recursively add directory contents
        if ($isDirectory) {
            foreach ($resource as $entry) {
                /** @var ResourceInterface $entry */
                $this->addResource($path.'/'.$entry->getName(), $entry);
            }
        }
    }
}
