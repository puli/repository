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

use Webmozart\Puli\Resource\DirectoryResource;
use Webmozart\Puli\ResourceRepositoryInterface;
use Webmozart\Puli\Tests\Resource\AbstractDirectoryResourceTest;

/**
 * @since  1.0
 * @author Bernhard Schussek <bschussek@gmail.com>
 */
class DirectoryResourceTest extends AbstractAttachableDirectoryResourceTest
{
    protected function createDir()
    {
        return new DirectoryResource();
    }

    protected function createAttachedDir(ResourceRepositoryInterface $repo, $path)
    {
        return DirectoryResource::createAttached($repo, $path);
    }

    protected function createFile($path)
    {
        return new TestFile($path);
    }
}
