<?php

/*
 * This file is part of the puli/repository package.
 *
 * (c) Bernhard Schussek <bschussek@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Puli\Repository\Resource\Collection;

use InvalidArgumentException;
use IteratorAggregate;
use OutOfBoundsException;
use Puli\Repository\Resource\Iterator\ResourceCollectionIterator;
use Puli\Repository\Resource\Resource;
use Puli\Repository\UnsupportedResourceException;
use Traversable;

/**
 * A collection of {@link Resource} instances backed by an array.
 *
 * @since  1.0
 * @author Bernhard Schussek <bschussek@gmail.com>
 */
class ArrayResourceCollection implements IteratorAggregate, ResourceCollection
{
    /**
     * @var Resource[]
     */
    private $resources;

    /**
     * Creates a new collection.
     *
     * You can pass the resources that you want to initially store in the
     * collection as argument.
     *
     * @param Resource[] $resources The resources to store in the collection.
     *
     * @throws InvalidArgumentException If the resources are not an array and
     *                                  not a traversable object.
     * @throws UnsupportedResourceException If a resource does not implement
     *                                      {@link Resource}.
     */
    public function __construct($resources = array())
    {
        $this->replace($resources);
    }

    /**
     * {@inheritdoc}
     */
    public function add(Resource $resource)
    {
        $this->resources[] = $resource;
    }

    /**
     * {@inheritdoc}
     */
    public function set($key, Resource $resource)
    {
        $this->resources[$key] = $resource;
    }

    /**
     * {@inheritdoc}
     */
    public function get($key)
    {
        if (!isset($this->resources[$key])) {
            throw new OutOfBoundsException(sprintf(
                'The offset "%s" does not exist.',
                $key
            ));
        }

        return $this->resources[$key];
    }

    /**
     * {@inheritdoc}
     */
    public function remove($key)
    {
        unset($this->resources[$key]);
    }

    /**
     * {@inheritdoc}
     */
    public function has($key)
    {
        return isset($this->resources[$key]);
    }

    /**
     * {@inheritdoc}
     */
    public function clear()
    {
        $this->resources = array();
    }

    /**
     * {@inheritdoc}
     */
    public function keys()
    {
        return array_keys($this->resources);
    }

    /**
     * {@inheritdoc}
     */
    public function replace($resources)
    {
        if (!is_array($resources) && !$resources instanceof Traversable) {
            throw new InvalidArgumentException(sprintf(
                'The resources must be passed as array or traversable object. '.
                'Got: "%s"',
                is_object($resources) ? get_class($resources) : gettype($resources)
            ));
        }

        foreach ($resources as $resource) {
            if (!$resource instanceof Resource) {
                throw new UnsupportedResourceException(sprintf(
                    'ArrayResourceCollection supports Resource implementations '.
                    'only. Got: %s',
                    is_object($resource) ? get_class($resource) : gettype($resource)
                ));
            }
        }

        $this->resources = is_array($resources) ? $resources : iterator_to_array($resources);
    }

    /**
     * {@inheritdoc}
     */
    public function merge($resources)
    {
        if (!is_array($resources) && !$resources instanceof Traversable) {
            throw new InvalidArgumentException(sprintf(
                'The resources must be passed as array or traversable object. '.
                'Got: "%s"',
                is_object($resources) ? get_class($resources) : gettype($resources)
            ));
        }

        foreach ($resources as $resource) {
            if (!$resource instanceof Resource) {
                throw new UnsupportedResourceException(sprintf(
                    'ArrayResourceCollection supports Resource implementations '.
                    'only. Got: %s',
                    is_object($resource) ? get_class($resource) : gettype($resource)
                ));
            }
        }

        // only start merging after validating all resources
        foreach ($resources as $resource) {
            $this->resources[] = $resource;
        }
    }

    /**
     * {@inheritdoc}
     */
    public function isEmpty()
    {
        return 0 === count($this->resources);
    }

    /**
     * {@inheritdoc}
     */
    public function offsetExists($key)
    {
        return $this->has($key);
    }

    /**
     * {@inheritdoc}
     */
    public function offsetGet($key)
    {
        return $this->get($key);
    }

    /**
     * {@inheritdoc}
     */
    public function offsetSet($key, $value)
    {
        if (null !== $key) {
            $this->set($key, $value);
        } else {
            $this->add($value);
        }
    }

    /**
     * {@inheritdoc}
     */
    public function offsetUnset($key)
    {
        $this->remove($key);
    }

    /**
     * {@inheritdoc}
     */
    public function getPaths()
    {
        return array_map(
            function (Resource $r) { return $r->getPath(); },
            $this->resources
        );
    }

    /**
     * {@inheritdoc}
     */
    public function getNames()
    {
        return array_map(
            function (Resource $r) { return $r->getName(); },
            $this->resources
        );
    }

    public function count()
    {
        return count($this->resources);
    }

    public function getIterator($mode = ResourceCollectionIterator::KEY_AS_CURSOR)
    {
        return new ResourceCollectionIterator($this, $mode);
    }

    public function toArray()
    {
        return $this->resources;
    }
}
