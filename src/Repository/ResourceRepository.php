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

/**
 * @since  %%NextVersion%%
 * @author Bernhard Schussek <bschussek@gmail.com>
 */
class ResourceRepository implements ResourceRepositoryInterface
{
    private $paths = array();

    /**
     * @var \Webmozart\Puli\Resource\ResourceInterface[]
     */
    private $resources = array();

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

    public function listDirectory($selector)
    {

    }

    public function add($selector, $realPath)
    {
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

        // Recursively add directory contents
        if ($isDirectory) {
            $iterator = new \FilesystemIterator($realPath, \FilesystemIterator::SKIP_DOTS | \FilesystemIterator::CURRENT_AS_PATHNAME);

            foreach ($iterator as $path) {
                $this->add($selector.'/'.basename($path), $path);
            }
        }

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

            // Keep the resources sorted by file name. This could probably be
            // optimized by inserting at the right position instead of
            // rearranging the complete array on every add.
            ksort($this->resources);

            return;
        }

        $this->paths[$selector][] = $realPath;
        $this->resources[$selector]->refresh($this);
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

                unset($this->resources[$path]);
                unset($this->paths[$path]);
            }

            return;
        }

        if (is_array($selector)) {
            foreach ($selector as $path) {
                $this->remove($path);
            }

            return;
        }

        if (isset($this->resources[$selector])) {
            unset($this->resources[$selector]);
            unset($this->paths[$selector]);
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
}
