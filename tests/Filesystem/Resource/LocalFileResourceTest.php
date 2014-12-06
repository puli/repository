<?php

/*
 * This file is part of the puli/repository package.
 *
 * (c) Bernhard Schussek <bschussek@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Puli\Repository\Tests\Filesystem\Resource;

use Puli\Repository\Filesystem\Resource\LocalDirectoryResource;
use Puli\Repository\Filesystem\Resource\LocalFileResource;
use Puli\Repository\Tests\Resource\AbstractFileResourceTest;

/**
 * @since  1.0
 * @author Bernhard Schussek <bschussek@gmail.com>
 */
class LocalFileResourceTest extends \PHPUnit_Framework_TestCase
{
    private $fixturesDir;

    protected function setUp()
    {
        $this->fixturesDir = realpath(__DIR__.'/Fixtures');
        parent::setUp();
    }

    protected function createFile()
    {
        return new LocalFileResource($this->fixturesDir.'/dir1/file1');
    }

    /**
     * @expectedException \Puli\Repository\UnsupportedResourceException
     */
    public function testOverrideFailsIfLocalResource()
    {
        $file = new LocalFileResource($this->fixturesDir.'/dir1/file1');

        $file->override(new TestLocalResource($this->fixturesDir.'/dir1/file1'));
    }

    /**
     * @expectedException \Puli\Repository\UnsupportedResourceException
     */
    public function testOverrideFailsIfLocalDirectoryResource()
    {
        $file = new LocalFileResource($this->fixturesDir.'/dir1/file1');

        $file->override(new LocalDirectoryResource($this->fixturesDir.'/dir1'));
    }

    /**
     * @expectedException \Puli\Repository\Filesystem\FilesystemException
     */
    public function testFailIfNoFile()
    {
        new LocalFileResource($this->fixturesDir.'/dir1');
    }

    public function testGetContents()
    {
        $file = new LocalFileResource($this->fixturesDir.'/dir1/file1');

        $this->assertSame(file_get_contents($file->getLocalPath()), $file->getContents());
    }
}
