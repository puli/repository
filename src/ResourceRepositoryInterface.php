<?php

/*
 * This file is part of the Puli package.
 *
 * (c) Bernhard Schussek <bschussek@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Webmozart\Puli;

use Webmozart\Puli\Resource\Collection\ResourceCollectionInterface;
use Webmozart\Puli\Resource\ResourceInterface;

/**
 * @since  1.0
 * @author Bernhard Schussek <bschussek@gmail.com>
 */
interface ResourceRepositoryInterface
{
    /**
     * @param string $path
     *
     * @return ResourceInterface
     */
    public function get($path);

    /**
     * @param string $selector
     *
     * @return ResourceCollectionInterface
     */
    public function find($selector);

    /**
     * @param string $selector
     *
     * @return boolean
     */
    public function contains($selector);

    /**
     * @param string $tag
     *
     * @return ResourceCollectionInterface
     */
    public function getByTag($tag);

    /**
     * @return string[]
     */
    public function getTags();
}
