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
interface ResourceInterface
{
    public function getPath();

    public function getName();

    /**
     * @param $path
     *
     * @return static
     */
    public function copyTo($path);

    /**
     * @param ResourceInterface $resource
     *
     * @return static
     *
     * @throws UnsupportedResourceException
     */
    public function override(ResourceInterface $resource);
}
