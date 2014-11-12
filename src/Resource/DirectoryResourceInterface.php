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

use Webmozart\Puli\Resource\Collection\ResourceCollectionInterface;

/**
 * @since  1.0
 * @author Bernhard Schussek <bschussek@gmail.com>
 */
interface DirectoryResourceInterface extends ResourceInterface
{
    /**
     * @param string $name
     *
     * @return ResourceInterface|ResourceInterface[]
     */
    public function get($name);

    /**
     * @param string $name
     *
     * @return boolean
     */
    public function contains($name);

    /**
     * @return ResourceCollectionInterface|ResourceInterface[]
     */
    public function listEntries();
}
