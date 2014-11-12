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

use Webmozart\Puli\Resource\ResourceInterface;

/**
 * A collection of {@link ResourceInterface} instances.
 *
 * @since  1.0
 * @author Bernhard Schussek <bschussek@gmail.com>
 */
interface ResourceCollectionInterface extends \Traversable, \ArrayAccess, \Countable
{
    public function add(ResourceInterface $resource);

    public function get($key);

    public function remove($key);

    public function has($key);

    public function clear();

    public function keys();

    public function replace($resources);

    public function isEmpty();

    public function getPaths();

    public function getNames();

    public function toArray();
}
