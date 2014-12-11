<?php

/*
 * This file is part of the puli/repository package.
 *
 * (c) Bernhard Schussek <bschussek@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Puli\Repository\Filesystem\Resource;

use Puli\Repository\Filesystem\FilesystemException;
use Puli\Repository\Resource\FileResourceInterface;
use Puli\Repository\Resource\ResourceInterface;
use Puli\Repository\UnsupportedResourceException;

/**
 * Represents a file on the local file system.
 *
 * @since  1.0
 * @author Bernhard Schussek <bschussek@gmail.com>
 */
class LocalFileResource extends AbstractLocalResource implements FileResourceInterface
{
    /**
     * {@inheritdoc}
     */
    public function __construct($localPath, $path = null, OverriddenPathLoaderInterface $pathLoader = null)
    {
        parent::__construct($localPath, $path, $pathLoader);

        if (!is_file($localPath)) {
            throw new FilesystemException(sprintf(
                'The path "%s" is not a file.',
                $localPath
            ));
        }
    }

    /**
     * {@inheritdoc}
     */
    public function getContents()
    {
        return file_get_contents($this->getLocalPath());
    }

    /**
     * {@inheritdoc}
     */
    public function getSize()
    {
        return filesize($this->getLocalPath());
    }

    /**
     * {@inheritdoc}
     */
    public function getLastAccessedAt()
    {
        return fileatime($this->getLocalPath());
    }

    /**
     * {@inheritdoc}
     */
    public function getLastModifiedAt()
    {
        return filemtime($this->getLocalPath());
    }

    /**
     * {@inheritdoc}
     */
    public function override(ResourceInterface $file)
    {
        if (!($file instanceof FileResourceInterface && $file instanceof LocalResourceInterface)) {
            throw new UnsupportedResourceException('Can only override other local file resources.');
        }

        parent::override($file);
    }
}
