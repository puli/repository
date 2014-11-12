<?php

/*
 * This file is part of the Puli package.
 *
 * (c) Bernhard Schussek <bschussek@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Webmozart\Puli\Uri;

use Webmozart\Puli\Resource\Collection\ResourceCollection;
use Webmozart\Puli\ResourceRepositoryInterface;

/**
 * @since  1.0
 * @author Bernhard Schussek <bschussek@gmail.com>
 */
class UriRepository implements UriRepositoryInterface
{
    /**
     * @var callable[]|ResourceRepositoryInterface[]
     */
    private $repos = array();

    public function register($scheme, $repoFactory)
    {
        if (!$repoFactory instanceof ResourceRepositoryInterface
                && !is_callable($repoFactory)) {
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

        $this->repos[$scheme] = $repoFactory;
    }

    public function unregister($scheme)
    {
        unset($this->repos[$scheme]);
    }

    public function getSupportedSchemes()
    {
        return array_keys($this->repos);
    }

    public function get($uri)
    {
        $parts = Uri::parse($uri);

        return $this->getRepository($parts['scheme'])->get($parts['path']);
    }

    public function find($uri)
    {
        $parts = Uri::parse($uri);

        return $this->getRepository($parts['scheme'])->find($parts['path']);
    }

    public function contains($uri)
    {
        $parts = Uri::parse($uri);

        return $this->getRepository($parts['scheme'])->contains($parts['path']);
    }

    public function getByTag($tag)
    {
        $resources = array();

        foreach ($this->repos as $repo) {
            foreach ($repo->getByTag($tag) as $resource) {
                $resources[] = $resource;
            }
        }

        return new ResourceCollection($resources);
    }

    public function getTags()
    {
        $tags = array();

        foreach ($this->repos as $repo) {
            foreach ($repo->getTags() as $tag) {
                $tags[$tag] = true;
            }
        }

        return array_keys($tags);
    }

    private function getRepository($scheme)
    {
        if (!isset($this->repos[$scheme])) {
            throw new SchemeNotSupportedException(sprintf(
                'The scheme "%s" is not supported.',
                $scheme
            ));
        }

        if (is_callable($this->repos[$scheme])) {
            $callable = $this->repos[$scheme];
            $result = $callable($scheme);

            if (!$result instanceof ResourceRepositoryInterface) {
                throw new RepositoryFactoryException(sprintf(
                    'The value of type "%s" returned by the locator factory '.
                    'registered for scheme "%s" does not implement '.
                    '"\Webmozart\Puli\Locator\ResourceLocatorInterface".',
                    gettype($result),
                    $scheme
                ));
            }

            $this->repos[$scheme] = $result;
        }

        return $this->repos[$scheme];
    }
}
