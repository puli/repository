<?php

/*
 * This file is part of the Puli package.
 *
 * (c) Bernhard Schussek <bschussek@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Puli\Filesystem\Resource;

use Puli\Filesystem\FilesystemException;
use Puli\Repository\UnsupportedResourceException;
use Puli\Resource\FileResourceInterface;
use Puli\Resource\ResourceInterface;

/**
 * @since  1.0
 * @author Bernhard Schussek <bschussek@gmail.com>
 */
class LocalFileResource extends LocalResource implements FileResourceInterface
{
    public function __construct($localPath)
    {
        parent::__construct($localPath);

        if (!is_file($localPath)) {
            throw new FilesystemException(sprintf(
                'The path "%s" is not a file.',
                $localPath
            ));
        }
    }

    public function getContents()
    {
        return file_get_contents($this->getLocalPath());
    }

    public function getSize()
    {
        return filesize($this->getLocalPath());
    }

    public function getLastAccessedAt()
    {
        return fileatime($this->getLocalPath());
    }

    public function getLastModifiedAt()
    {
        return filemtime($this->getLocalPath());
    }

    public function override(ResourceInterface $file)
    {
        if (!($file instanceof FileResourceInterface && $file instanceof LocalResourceInterface)) {
            throw new UnsupportedResourceException('Can only override other local file resources.');
        }

        parent::override($file);
    }
}
