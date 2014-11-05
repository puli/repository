<?php

/*
 * This file is part of the Puli package.
 *
 * (c) Bernhard Schussek <bschussek@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Webmozart\Puli\Tests\Resource;

use Webmozart\Puli\Resource\DirectoryLoaderInterface;
use Webmozart\Puli\Resource\DirectoryResource;
use Webmozart\Puli\Resource\FileResourceInterface;

/**
 * @since  1.0
 * @author Bernhard Schussek <bschussek@gmail.com>
 */
class DirectoryResourceTest extends AbstractDirectoryResourceTest
{
    protected function createDir($path, DirectoryLoaderInterface $loader = null)
    {
        return DirectoryResource::forPath($path, $loader);
    }

    protected function createFile($path)
    {
        return new TestFile($path);
    }
}
