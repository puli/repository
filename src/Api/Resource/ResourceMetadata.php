<?php

/*
 * This file is part of the puli/repository package.
 *
 * (c) Bernhard Schussek <bschussek@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Puli\Repository\Api\Resource;

/**
 * Contains metadata about a resource.
 *
 * @since  1.0
 * @author Bernhard Schussek <bschussek@gmail.com>
 */
interface ResourceMetadata
{
    /**
     * Returns when the resource was created.
     *
     * If this information is not available, the method returns 0.
     *
     * @return integer A UNIX timestamp.
     */
    public function getCreationTime();

    /**
     * Returns when the resource was last accessed.
     *
     * If this information is not available, the method returns 0.
     *
     * @return integer A UNIX timestamp.
     */
    public function getAccessTime();

    /**
     * Returns when the resource was last modified.
     *
     * If this information is not available, the method returns 0.
     *
     * @return integer A UNIX timestamp.
     */
    public function getModificationTime();
}
