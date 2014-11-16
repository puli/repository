<?php

/*
 * This file is part of the Puli package.
 *
 * (c) Bernhard Schussek <bschussek@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Puli\Resource\Collection;

use Puli\Repository\UnsupportedResourceException;
use Puli\Resource\ResourceInterface;

/**
 * A collection of {@link ResourceInterface} instances.
 *
 * @since  1.0
 * @author Bernhard Schussek <bschussek@gmail.com>
 */
interface ResourceCollectionInterface extends \Traversable, \ArrayAccess, \Countable
{
    /**
     * Adds a resource to the collection.
     *
     * @param ResourceInterface $resource The added resource.
     */
    public function add(ResourceInterface $resource);

    /**
     * Sets a resource at a collection key.
     *
     * @param integer           $key      The collection key.
     * @param ResourceInterface $resource The resource to set.
     */
    public function set($key, ResourceInterface $resource);

    /**
     * Returns the resource for a collection key.
     *
     * @param integer $key The collection key.
     *
     * @return ResourceInterface The resource at the key.
     *
     * @throws \OutOfBoundsException If the key does not exist.
     */
    public function get($key);

    /**
     * Removes a collection key from the collection.
     *
     * @param integer $key The collection key.
     */
    public function remove($key);

    /**
     * Returns whether a collection key exists.
     *
     * @param integer $key The collection key.
     *
     * @return bool Whether the collection key exists.
     */
    public function has($key);

    /**
     * Removes all resources from the collection.
     */
    public function clear();

    /**
     * Returns the keys of the collection.
     *
     * @return integer[] The collection keys.
     */
    public function keys();

    /**
     * Replaces the collection contents with the given resources.
     *
     * @param ResourceInterface[] $resources The resources to write into the
     *                                       collection.
     *
     * @throws \InvalidArgumentException If the resources are not an array and
     *                                   not a traversable object.
     * @throws UnsupportedResourceException If a resource does not implement
     *                                      {@link ResourceInterface}.
     */
    public function replace($resources);

    /**
     * Merges the given resources into the collection.
     *
     * @param ResourceInterface[] $resources The resources to merge into the
     *                                       collection.
     *
     * @throws \InvalidArgumentException If the resources are not an array and
     *                                   not a traversable object.
     * @throws UnsupportedResourceException If a resource does not implement
     *                                      {@link ResourceInterface}.
     */
    public function merge($resources);

    /**
     * Returns whether the collection is empty.
     *
     * @return bool Returns `true` only if the collection contains no resources.
     */
    public function isEmpty();

    /**
     * Returns the paths of all resources in the collection.
     *
     * The paths are returned in the order of their resources in the collection.
     *
     * @return string[] The paths of the resources in the collection.
     *
     * @see ResourceInterface::getPath
     */
    public function getPaths();

    /**
     * Returns the names of all resources in the collection.
     *
     * The names are returned in the order of their resources in the collection.
     *
     * @return string[] The names of the resources in the collection.
     *
     * @see ResourceInterface::getName
     */
    public function getNames();

    /**
     * Returns the collection contents as array.
     *
     * @return ResourceInterface[] The resources in the collection.
     */
    public function toArray();
}
