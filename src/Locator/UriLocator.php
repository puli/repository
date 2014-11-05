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

use Webmozart\Puli\Resource\ResourceCollection;
use Webmozart\Puli\Uri\Uri;

/**
 * @since  1.0
 * @author Bernhard Schussek <bschussek@gmail.com>
 */
class UriLocator implements UriLocatorInterface
{
    /**
     * @var callable[]|ResourceLocatorInterface[]
     */
    private $locators = array();

    public function register($scheme, $locatorFactory)
    {
        if (!$locatorFactory instanceof ResourceLocatorInterface
                && !is_callable($locatorFactory)) {
            throw new \InvalidArgumentException(
                'The locator factory should be a callable or an instance '.
                'of "Webmozart\Puli\Locator\ResourceLocatorInterface".'
            );
        }

        if (!is_string($scheme)) {
            throw new \InvalidArgumentException(sprintf(
                'The scheme must be a string, but is a "%s".',
                gettype($scheme)
            ));
        }

        if (!ctype_alpha($scheme)) {
            throw new \InvalidArgumentException(sprintf(
                'The scheme "%s" should consist of letters only.',
                $scheme
            ));
        }

        $this->locators[$scheme] = $locatorFactory;
    }

    public function unregister($scheme)
    {
        unset($this->locators[$scheme]);
    }

    public function getRegisteredSchemes()
    {
        return array_keys($this->locators);
    }

    public function get($uri)
    {
        $parts = Uri::parse($uri);

        return $this->getLocator($parts['scheme'])->get($parts['path']);
    }

    public function find($uri)
    {
        $parts = Uri::parse($uri);

        return $this->getLocator($parts['scheme'])->find($parts['path']);
    }

    public function contains($uri)
    {
        $parts = Uri::parse($uri);

        return $this->getLocator($parts['scheme'])->contains($parts['path']);
    }

    public function getByTag($tag)
    {
        $resources = array();

        foreach ($this->locators as $locator) {
            foreach ($locator->getByTag($tag) as $resource) {
                $resources[] = $resource;
            }
        }

        return new ResourceCollection($resources);
    }

    public function listDirectory($uri)
    {
        $parts = Uri::parse($uri);

        return $this->getLocator($parts['scheme'])->listDirectory($parts['path']);
    }

    public function getTags()
    {
        $tags = array();

        foreach ($this->locators as $locator) {
            foreach ($locator->getTags() as $tag) {
                $tags[$tag] = true;
            }
        }

        return array_keys($tags);
    }

    private function getLocator($scheme)
    {
        if (!isset($this->locators[$scheme])) {
            throw new SchemeNotSupportedException(sprintf(
                'The scheme "%s" is not supported.',
                $scheme
            ));
        }

        if (is_callable($this->locators[$scheme])) {
            $callable = $this->locators[$scheme];
            $result = $callable($scheme);

            if (!$result instanceof ResourceLocatorInterface) {
                throw new LocatorFactoryException(sprintf(
                    'The value of type "%s" returned by the locator factory '.
                    'registered for scheme "%s" does not implement '.
                    '"\Webmozart\Puli\Locator\ResourceLocatorInterface".',
                    gettype($result),
                    $scheme
                ));
            }

            $this->locators[$scheme] = $result;
        }

        return $this->locators[$scheme];
    }
}
