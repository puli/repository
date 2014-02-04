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

use Webmozart\Puli\Locator\AbstractResourceLocator;
use Webmozart\Puli\Locator\ResourceNotFoundException;
use Webmozart\Puli\Pattern\PatternInterface;
use Webmozart\Puli\PatternLocator\GlobPatternLocator;
use Webmozart\Puli\PatternLocator\PatternFactoryInterface;
use Webmozart\Puli\PatternLocator\PatternLocatorInterface;
use Webmozart\Puli\Resource\DirectoryResource;
use Webmozart\Puli\Resource\FileResource;
use Webmozart\Puli\Tag\Tag;

/**
 * @since  %%NextVersion%%
 * @author Bernhard Schussek <bschussek@gmail.com>
 */
class ResourceRepository extends AbstractResourceLocator implements ResourceRepositoryInterface
{
    /**
     * @var FileResource[]|DirectoryResource[]
     */
    private $resources = array();

    /**
     * @var Tag[]
     */
    private $tags = array();

    /**
     * @var PatternLocatorInterface
     */
    private $patternLocator;

    public function __construct(PatternFactoryInterface $patternFactory = null)
    {
        parent::__construct($patternFactory);

        $this->resources['/'] = new DirectoryResource('/', null);
    }

    public function getByTag($tag)
    {
        if (!isset($this->tags[$tag])) {
            return array();
        }

        return iterator_to_array($this->tags[$tag]);
    }

    public function add($selector, $realPath)
    {
        // Discard any trailing slashes of directories. We don't need them.
        $selector = rtrim($selector, '/');

        if (is_string($realPath) && $this->patternFactory->acceptsSelector($realPath)) {
            $realPath = $this->patternFactory->createPattern($realPath);
        }

        if ($realPath instanceof PatternInterface) {
            if (null === $this->patternLocator) {
                $this->patternLocator = $this->patternFactory->createPatternLocator();
            }

            $realPath = $this->patternLocator->locatePaths($realPath);
        }

        if (is_array($realPath)) {
            foreach ($realPath as $path) {
                if (is_string($path) && $this->patternFactory->acceptsSelector($path)) {
                    // Immediately create the pattern in order to avoid another
                    // test of the selector in the recursive call.
                    $this->add($selector, $this->patternFactory->createPattern($path));

                    continue;
                }

                $this->add($selector.'/'.basename($path), $path);
            }

            return;
        }

        if (!is_string($realPath)) {
            throw new \InvalidArgumentException(sprintf(
                'The argument $realPath should be a string, an array or '.
                'Webmozart\\Puli\\Pattern\\PatternInterface, but is: %s.',
                is_object($realPath) ? get_class($realPath) : gettype($realPath)
            ));
        }

        if (!file_exists($realPath)) {
            throw new ResourceNotFoundException(sprintf(
                'The file "%s" could not be found.',
                $realPath
            ));
        }

        $isDirectory = is_dir($realPath);

        // Create new Resource instances if necessary
        if (!isset($this->resources[$selector])) {
            $this->resources[$selector] = $isDirectory
                ? new DirectoryResource(
                    $selector,
                    $realPath
                )
                : new FileResource(
                    $selector,
                    $realPath
                );

            // Create parent directory if needed
            $parent = dirname($selector);

            if (!isset($this->resources[$parent])) {
                $this->initDirectory($parent);
            }

            // Keep the resources sorted by file name. This could probably be
            // optimized by inserting at the right position instead of
            // rearranging the complete array on every add.
            ksort($this->resources);

            // Add the new node to the parent directory
            $this->resources[$parent]->add($this->resources[$selector]);
        } else {
            $this->resources[$selector]->overridePath($realPath);
        }

        // Recursively add directory contents
        if ($isDirectory) {
            $iterator = new \FilesystemIterator($realPath, \FilesystemIterator::SKIP_DOTS | \FilesystemIterator::CURRENT_AS_PATHNAME);

            foreach ($iterator as $path) {
                $this->add($selector.'/'.basename($path), $path);
            }
        }
    }

    public function remove($selector)
    {
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

        $selector = rtrim($selector, '/');

        if ('' === $selector) {
            throw new RemovalNotAllowedException(
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

        if (!is_array($resources)) {
            $resources = array($resources);
        }

        if (!isset($this->tags[$tag])) {
            $this->tags[$tag] = new Tag($tag);

            // Maintain order
            ksort($this->tags);
        }

        foreach ($resources as $resource) {
            $this->tags[$tag]->addResource($resource);
        }
    }

    public function untag($selector, $tag = null)
    {
        $resources = $this->get($selector);

        if (!is_array($resources)) {
            $resources = array($resources);
        }

        if (null !== $tag) {
            if (!isset($this->tags[$tag])) {
                return;
            }

            foreach ($resources as $resource) {
                $this->tags[$tag]->removeResource($resource);
            }

            // Clean up
            if (0 === count($this->tags[$tag])) {
                unset($this->tags[$tag]);
            }

            return;
        }

        /** @var \Webmozart\Puli\Resource\ResourceInterface $resource */
        foreach ($resources as $resource) {
            foreach ($resource->getTags() as $tag) {
                $tag->removeResource($resource);

                // Clean up
                if (0 === count($tag)) {
                    unset($this->tags[$tag->getName()]);
                }
            }
        }
    }

    /**
     * {@inheritdoc}
     */
    public function getTags()
    {
        // Discard keys so that the using class does not depend on the internal
        // implementation
        return array_values($this->tags);
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

        return $resources;
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
        if (isset($this->resources[$parent = dirname($repositoryPath)])) {
            $this->resources[$parent]->remove($resource->getName());
        }

        // Recursively remove all children
        if ($resource instanceof DirectoryResource) {
            foreach ($resource as $entry) {
                /** @var \Webmozart\Puli\Resource\ResourceInterface $entry */
                $this->removeNode($entry->getRepositoryPath());
            }
        }

        // Untag resource
        foreach ($resource->getTags() as $tag) {
            $tag->removeResource($resource);

            // Clean up
            if (0 === count($tag)) {
                unset($this->tags[$tag->getName()]);
            }
        }
    }

    private function initDirectory($repositoryPath)
    {
        // Create new directory
        $directory = new DirectoryResource($repositoryPath, null);

        // Recursively initialize parent directories
        $parent = dirname($repositoryPath);

        if (!isset($this->resources[$parent])) {
            $this->initDirectory($parent);
        }

        // Add as child node of the parent directory
        $this->resources[$parent]->add($directory);

        // Register after parent directory to keep the order
        $this->resources[$repositoryPath] = $directory;
    }
}
