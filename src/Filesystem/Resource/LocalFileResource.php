<?php

/*
 * This file is part of the Puli package.
 *
 * (c) Bernhard Schussek <bschussek@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Webmozart\Puli\Filesystem\Resource;

use Webmozart\Puli\Filesystem\FilesystemException;
use Webmozart\Puli\Resource\FileResourceInterface;
use Webmozart\Puli\Resource\ResourceInterface;
use Webmozart\Puli\Resource\UnsupportedResourceException;

/**
 * @since  1.0
 * @author Bernhard Schussek <bschussek@gmail.com>
 */
class LocalFileResource extends LocalResource implements FileResourceInterface
{
    public function __construct($localPath, AlternativePathLoaderInterface $alternativesLoader = null)
    {
        parent::__construct($localPath, $alternativesLoader);

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

    public function override(ResourceInterface $file)
    {
        if (!($file instanceof FileResourceInterface && $file instanceof LocalResourceInterface)) {
            throw new UnsupportedResourceException('Can only override other local file resources.');
        }

        return parent::override($file);
    }
}
