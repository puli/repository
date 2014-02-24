<?php

/*
 * This file is part of the Puli package.
 *
 * (c) Bernhard Schussek <bschussek@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Webmozart\Puli\Resource;

/**
 * A collection of resources.
 *
 * @since  1.0
 * @author Bernhard Schussek <bschussek@gmail.com>
 */
class ResourceCollection implements \IteratorAggregate, ResourceCollectionInterface
{
    private $resources;

    public function __construct($resources = array())
    {
        $this->replace($resources);
    }

    public function add(ResourceInterface $resource)
    {
        $this->resources[] = $resource;
    }

    public function get($key)
    {
        if (!isset($this->resources[$key])) {
            throw new \OutOfBoundsException(sprintf(
                'The offset "%s" does not exist.',
                $key
            ));
        }

        return $this->resources[$key];
    }

    public function remove($key)
    {
        unset($this->resources[$key]);
    }

    public function has($key)
    {
        return isset($this->resources[$key]);
    }

    public function clear()
    {
        $this->resources = array();
    }

    public function keys()
    {
        return array_keys($this->resources);
    }

    public function replace($resources)
    {
        if (!is_array($resources) && !$resources instanceof \Traversable) {
            throw new \InvalidArgumentException(sprintf(
                'The resources must be passed as array or traversable object. '.
                'Got: "%s"',
                is_object($resources) ? get_class($resources) : gettype($resources)
            ));
        }

        $this->resources = is_array($resources) ? $resources : iterator_to_array($resources);
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
        if (null !== $key) {
            $this->resources[$key] = $value;
        } else {
            $this->resources[] = $value;
        }
    }

    public function offsetUnset($key)
    {
        $this->remove($key);
    }

    public function getPaths()
    {
        return array_map(
            function (ResourceInterface $r) { return $r->getPath(); },
            $this->resources
        );
    }

    public function getNames()
    {
        return array_map(
            function (ResourceInterface $r) { return $r->getName(); },
            $this->resources
        );
    }

    public function getRealPaths()
    {
        return array_map(
            function (ResourceInterface $r) { return $r->getRealPath(); },
            $this->resources
        );
    }

    public function count()
    {
        return count($this->resources);
    }

    public function getIterator()
    {
        return new ResourceCollectionIterator($this, ResourceCollectionIterator::KEY_AS_CURSOR);
    }

    public function toArray()
    {
        return $this->resources;
    }
}
