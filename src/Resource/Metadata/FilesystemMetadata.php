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
 * 
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
     * On Windows, fileXtime functions see only changes
     * on the symlink file and not the original one.
     *
     * @param string $path
     *
     * @return string
     */
    private function fixWindowsPath($path)
    {
        if (is_link($path) && defined('PHP_WINDOWS_VERSION_MAJOR')) {
            $path = readlink($path);
        }

        return $path;
    }

    /**
     * {@inheritdoc}
     */
    public function getCreationTime()
    {
        if (defined('PHP_WINDOWS_VERSION_MAJOR')) {
            $path = $this->fixWindowsPath($this->filesystemPath);
            clearstatcache(true, $path);

            return filectime($path);
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
        $path = $this->fixWindowsPath($this->filesystemPath);
        clearstatcache(true, $path);

        return fileatime($path);
    }

    /**
     * {@inheritdoc}
     */
    public function getModificationTime()
    {
        $path = $this->fixWindowsPath($this->filesystemPath);
        clearstatcache(true, $path);

        return filemtime($path);
    }

    /**
     * {@inheritdoc}
     */
    public function getSize()
    {
        $path = $this->fixWindowsPath($this->filesystemPath);
        clearstatcache(true, $path);

        return filesize($path);
    }
}
