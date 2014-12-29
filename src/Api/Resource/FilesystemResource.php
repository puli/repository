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
 * A resource associated to a file on the file system.
 *
 * The path of the file can be accessed with {@link getFilesystemPath}.
 *
 * @since  1.0
 * @author Bernhard Schussek <bschussek@gmail.com>
 */
interface FilesystemResource extends Resource
{
    /**
     * Returns the path on the file system.
     *
     * @return string|null The file system path or `null` if the resource has no
     *                     associated local file.
     */
    public function getFilesystemPath();
}
