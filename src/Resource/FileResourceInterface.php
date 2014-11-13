<?php

/*
 * This file is part of the Puli package.
 *
 * (c) Bernhard Schussek <bschussek@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Puli\Resource;

/**
 * A resource that contains a file body.
 *
 * @since  1.0
 * @author Bernhard Schussek <bschussek@gmail.com>
 */
interface FileResourceInterface extends ResourceInterface
{
    /**
     * Returns the contents of the resource.
     *
     * @return string The resource contents.
     */
    public function getContents();

    /**
     * Returns the size of the contents in bytes.
     *
     * @return integer The content size in bytes.
     */
    public function getSize();

    /**
     * Returns when the resource was last accessed.
     *
     * If this information is not available, the method returns 0.
     *
     * @return integer A UNIX timestamp.
     */
    public function getLastAccessedAt();

    /**
     * Returns when the resource was last modified.
     *
     * If this information is not available, the method returns 0.
     *
     * @return integer A UNIX timestamp.
     */
    public function getLastModifiedAt();
}
