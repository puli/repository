<?php

/*
 * This file is part of the puli/repository package.
 *
 * (c) Bernhard Schussek <bschussek@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Puli\Repository\Resource\Metadata;

use Puli\Repository\Api\Resource\ResourceMetadata;

/**
 * Metadata about a file on the filesystem.
 *
 * @since  1.0
 * @author Bernhard Schussek <bschussek@gmail.com>
 */
class FilesystemMetadata extends ResourceMetadata
{
    private $filesystemPath;

    public function __construct($filesystemPath)
    {
        $this->filesystemPath = $filesystemPath;
    }

    /**
     * {@inheritdoc}
     */
    public function getCreationTime()
    {
        if (defined('PHP_WINDOWS_VERSION_MAJOR')) {
            clearstatcache(true, $this->filesystemPath);

            return filectime($this->filesystemPath);
        }

        // On Unix, filectime() returns the change time of the inode, not the
        // creation time.
        return 0;
    }

    /**
     * {@inheritdoc}
     */
    public function getAccessTime()
    {
        clearstatcache(true, $this->filesystemPath);

        return fileatime($this->filesystemPath);
    }

    /**
     * {@inheritdoc}
     */
    public function getModificationTime()
    {
        clearstatcache(true, $this->filesystemPath);

        return filemtime($this->filesystemPath);
    }

    /**
     * {@inheritdoc}
     */
    public function getSize()
    {
        clearstatcache(true, $this->filesystemPath);

        return filesize($this->filesystemPath);
    }
}
