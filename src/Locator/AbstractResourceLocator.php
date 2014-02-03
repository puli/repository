<?php

/*
 * This file is part of the Puli package.
 *
 * (c) Bernhard Schussek <bschussek@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Webmozart\Puli\Locator;

use Webmozart\Puli\Pattern\GlobPattern;
use Webmozart\Puli\Pattern\PatternInterface;
use Webmozart\Puli\Resource\DirectoryResourceInterface;

/**
 * @since  %%NextVersion%%
 * @author Bernhard Schussek <bschussek@gmail.com>
 */
abstract class AbstractResourceLocator implements ResourceLocatorInterface
{
    /**
     * {@inheritdoc}
     */
    public function get($selector)
    {
        if (is_string($selector) && false !== strpos($selector, '*')) {
            $selector = new GlobPattern($selector);
        }

        if ($selector instanceof PatternInterface) {
            return $this->getPatternImpl($selector);
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

        return $this->getImpl($selector);
    }

    public function listDirectory($repositoryPath)
    {
        $repositoryPath = rtrim($repositoryPath, '/');

        // If the selector is empty after trimming, reset it to root.
        if ('' === $repositoryPath) {
            $repositoryPath = '/';
        }

        $resource = $this->getImpl($repositoryPath);

        if ($resource instanceof DirectoryResourceInterface) {
            return $resource->all();
        }

        throw new \InvalidArgumentException(sprintf(
            'The resource "%s" is not a directory, but a file.',
            $repositoryPath
        ));
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
            return $this->containsPatternImpl($selector);
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

        return $this->containsImpl($selector);
    }

    abstract protected function getImpl($repositoryPath);

    abstract protected function getPatternImpl(PatternInterface $pattern);

    abstract protected function containsImpl($repositoryPath);

    abstract protected function containsPatternImpl(PatternInterface $pattern);
}
