<?php

/*
 * This file is part of the Puli package.
 *
 * (c) Bernhard Schussek <bschussek@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Webmozart\Puli\Resource\Collection;

use Webmozart\Puli\Resource\Iterator\ResourceCollectionIterator;
use Webmozart\Puli\Resource\ResourceInterface;
use Webmozart\Puli\ResourceRepositoryInterface;

/**
 * A lazily loaded resource collection.
 *
 * @since  1.0
 * @author Bernhard Schussek <bschussek@gmail.com>
 */
class LazyResourceCollection implements \IteratorAggregate, ResourceCollectionInterface
{
    /**
     * @var string[]|ResourceInterface[]
     */
    private $resources;

    /**
     * @var ResourceRepositoryInterface
     */
    private $repo;

    /**
     * @var boolean
     */
    private $loaded = false;

    public function __construct(ResourceRepositoryInterface $repo, array $paths)
    {
        $this->resources = $paths;
        $this->repo = $repo;
    }

    public function add(ResourceInterface $resource)
    {
        throw new \BadMethodCallException(
            'Lazy resource collections cannot be modified.'
        );
    }

    public function get($key)
    {
        if (!isset($this->resources[$key])) {
            throw new \OutOfBoundsException(sprintf(
                'The offset "%s" does not exist.',
                $key
            ));
        }

        if (!$this->resources[$key] instanceof ResourceInterface) {
            $this->resources[$key] = $this->repo->get($this->resources[$key]);
        }

        return $this->resources[$key];
    }

    public function remove($key)
    {
        throw new \BadMethodCallException(
            'Lazy resource collections cannot be modified.'
        );
    }

    public function has($key)
    {
        return isset($this->resources[$key]);
    }

    public function clear()
    {
        throw new \BadMethodCallException(
            'Lazy resource collections cannot be modified.'
        );
    }

    public function keys()
    {
        if (!$this->loaded) {
            $this->load();
        }

        return array_keys($this->resources);
    }

    public function replace($resources)
    {
        throw new \BadMethodCallException(
            'Lazy resource collections cannot be modified.'
        );
    }

    public function isEmpty()
    {
        return 0 === count($this->resources);
    }

    public function offsetExists($key)
    {
        return $this->has($key);
    }

    public function offsetGet($key)
    {
        return $this->get($key);
    }

    public function offsetSet($key, $value)
    {
        throw new \BadMethodCallException(
            'Lazy resource collections cannot be modified.'
        );
    }

    public function offsetUnset($key)
    {
        throw new \BadMethodCallException(
            'Lazy resource collections cannot be modified.'
        );
    }

    public function getPaths()
    {
        if (!$this->loaded) {
            $this->load();
        }

        return array_map(
            function (ResourceInterface $r) { return $r->getPath(); },
            $this->resources
        );
    }

    public function getNames()
    {
        if (!$this->loaded) {
            $this->load();
        }

        return array_map(
            function (ResourceInterface $r) { return $r->getName(); },
            $this->resources
        );
    }

    public function getIterator()
    {
        if (!$this->loaded) {
            $this->load();
        }

        return new ResourceCollectionIterator($this, ResourceCollectionIterator::KEY_AS_CURSOR);
    }

    public function toArray()
    {
        if (!$this->loaded) {
            $this->load();
        }

        return $this->resources;
    }

    public function count()
    {
        return count($this->resources);
    }

    private function load()
    {
        foreach ($this->resources as $key => $resource) {
            if (!$resource instanceof ResourceInterface) {
                $this->resources[$key] = $this->repo->get($resource);
            }
        }

        $this->loaded = true;
    }
}
