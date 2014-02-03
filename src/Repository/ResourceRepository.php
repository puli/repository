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

use Webmozart\Puli\Pattern\GlobPattern;
use Webmozart\Puli\Pattern\PatternInterface;
use Webmozart\Puli\Resource\DirectoryResource;
use Webmozart\Puli\Resource\FileResource;
use Webmozart\Puli\Resource\ResourceInterface;

/**
 * @since  %%NextVersion%%
 * @author Bernhard Schussek <bschussek@gmail.com>
 */
class ResourceRepository implements ResourceRepositoryInterface
{
    private $paths = array();

    /**
     * @var \Webmozart\Puli\Resource\FileResource[]|\Webmozart\Puli\Resource\DirectoryResource[]
     */
    private $resources = array();

    public function __construct()
    {
        $this->resources['/'] = new DirectoryResource('/', null);;
    }

    /**
     * {@inheritdoc}
     */
    public function get($selector)
    {
        if (is_string($selector) && false !== strpos($selector, '*')) {
            $selector = new GlobPattern($selector);
        }

        if ($selector instanceof PatternInterface) {
            $staticPrefix = $selector->getStaticPrefix();
            $regExp = $selector->getRegularExpression();

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

        if (is_array($selector)) {
            $resources = array();

            foreach ($selector as $path) {
                $result = $this->get($path);
                $result = is_array($result) ? $result : array($result);

                foreach ($result as $resource) {
                    $resources[] = $resource;
                }
            }

            return $resources;
        }

        $selector = rtrim($selector, '/');

        // If the selector is empty after trimming, reset it to root.
        if ('' === $selector) {
            $selector = '/';
        }

        if (!isset($this->resources[$selector])) {
            throw new ResourceNotFoundException(sprintf(
                'The resource "%s" does not exist.',
                $selector
            ));
        }

        return $this->resources[$selector];
    }

    public function getByTag($tag)
    {

    }

    public function listDirectory($repositoryPath)
    {
        $repositoryPath = rtrim($repositoryPath, '/');

        // If the selector is empty after trimming, reset it to root.
        if ('' === $repositoryPath) {
            $repositoryPath = '/';
        }

        if (!isset($this->resources[$repositoryPath])) {
            throw new ResourceNotFoundException(sprintf(
                'The resource "%s" does not exist.',
                $repositoryPath
            ));
        }

        if (!$this->resources[$repositoryPath] instanceof DirectoryResource) {
            throw new \InvalidArgumentException(sprintf(
                'The resource "%s" is not a directory, but a file.',
                $repositoryPath
            ));
        }

        return $this->resources[$repositoryPath]->all();
    }

    public function add($selector, $realPath)
    {
        // Discard any trailing slashes of directories. We don't need them.
        $selector = rtrim($selector, '/');

        if (is_string($realPath) && false !== strpos($realPath, '*')) {
            $realPath = new GlobPattern($realPath);
        }

        if ($realPath instanceof PatternInterface) {
            if (!$realPath instanceof GlobPattern) {
                throw new \InvalidArgumentException(sprintf(
                    'Currently, only GlobPattern is supported by add(). The '.
                    'passed pattern was an instance of %s.',
                    get_class($realPath)
                ));
            }

            $realPath = glob($realPath);
        }

        if (is_array($realPath)) {
            foreach ($realPath as $path) {
                if (false !== strpos($path, '*')) {
                    $this->add($selector, $path);

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

        $isDirectory = is_dir($realPath);

        // Create new Resource instances if necessary
        if (!isset($this->paths[$selector])) {
            $this->paths[$selector] = array($realPath);

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
            $this->paths[$selector][] = $realPath;
            $this->resources[$selector]->refresh($this);
        }

        // Recursively add directory contents
        if ($isDirectory) {
            $iterator = new \FilesystemIterator($realPath, \FilesystemIterator::SKIP_DOTS | \FilesystemIterator::CURRENT_AS_PATHNAME);

            foreach ($iterator as $path) {
                $this->add($selector.'/'.basename($path), $path);
            }
        }
    }

    /**
     * {@inheritdoc}
     */
    public function contains($selector)
    {
        if (is_string($selector) && false !== strpos($selector, '*')) {
            $selector = new GlobPattern($selector);
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

                return true;
            }

            return false;
        }

        if (is_array($selector)) {
            foreach ($selector as $path) {
                if (!$this->contains($path)) {
                    return false;
                }
            }

            return true;
        }

        $selector = rtrim($selector, '/');

        // If the selector is empty after trimming, reset it to root.
        if ('' === $selector) {
            $selector = '/';
        }

        return isset($this->resources[$selector]);
    }

    public function remove($selector)
    {
        if (is_string($selector) && false !== strpos($selector, '*')) {
            $selector = new GlobPattern($selector);
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

    }

    public function untag($selector, $tag = null)
    {

    }

    public function getTags($selector = null)
    {

    }

    public function getPaths($selector)
    {
        return $this->paths[$selector];
    }

    private function removeNode($repositoryPath)
    {
        $resource = $this->resources[$repositoryPath];

        if ($resource instanceof DirectoryResource) {
            foreach ($resource as $entry) {
                $this->removeNode($entry->getRepositoryPath());
            }
        }

        unset($this->resources[$repositoryPath]);
        unset($this->paths[$repositoryPath]);
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
