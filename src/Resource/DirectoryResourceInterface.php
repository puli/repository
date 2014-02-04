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
 * @since  1.0
 * @author Bernhard Schussek <bschussek@gmail.com>
 */
interface DirectoryResourceInterface extends ResourceInterface, \Traversable, \Countable, \ArrayAccess
{
    public function get($name);

    public function contains($name);

    public function all();

    /**
     * @internal You should use {@link \Webmozart\Puli\Repository\ResourceRepositoryInterface::add()}.
     */
    public function add(ResourceInterface $resource);

    /**
     * @internal You should use {@link \Webmozart\Puli\Repository\ResourceRepositoryInterface::remove()}.
     */
    public function remove($name);
}
