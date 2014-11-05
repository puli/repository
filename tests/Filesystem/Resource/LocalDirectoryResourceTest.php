<?php

/*
 * This file is part of the Puli package.
 *
 * (c) Bernhard Schussek <bschussek@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Webmozart\Puli\Tests\Filesystem\Resource;

use Webmozart\Puli\Filesystem\Resource\LocalDirectoryResource;
use Webmozart\Puli\Filesystem\Resource\LocalFileResource;
use Webmozart\Puli\Resource\DirectoryLoaderInterface;
use Webmozart\Puli\Tests\Resource\AbstractDirectoryResourceTest;

/**
 * @since  1.0
 * @author Bernhard Schussek <bschussek@gmail.com>
 */
class LocalDirectoryResourceTest extends AbstractDirectoryResourceTest
{
    private $fixturesDir;

    protected function setUp()
    {
        $this->fixturesDir = realpath(__DIR__.'/../../Fixtures');
        parent::setUp();
    }

    protected function createDir($path, DirectoryLoaderInterface $loader = null)
    {
        if (null === $loader) {
            $loader = $this->getMock('Webmozart\Puli\Resource\DirectoryLoaderInterface');

            $loader->expects($this->any())
                ->method('loadDirectoryEntries')
                ->will($this->returnValue(array()));
        }

        return LocalDirectoryResource::forPath($path, $this->fixturesDir.'/dir1', null, $loader);
    }

    protected function createFile($path)
    {
        return LocalFileResource::forPath($path, $this->fixturesDir.'/file3');
    }

    /**
     * @expectedException \Webmozart\Puli\Resource\UnsupportedResourceException
     */
    public function testOverrideFailsIfLocalResource()
    {
        $directory = new LocalDirectoryResource($this->fixturesDir.'/dir1');

        $directory->override(new TestLocalResource($this->fixturesDir.'/dir1/file1'));
    }

    /**
     * @expectedException \Webmozart\Puli\Resource\UnsupportedResourceException
     */
    public function testOverrideFailsIfLocalFileResource()
    {
        $directory = new LocalDirectoryResource($this->fixturesDir.'/dir1');

        $directory->override(new LocalFileResource($this->fixturesDir.'/dir1/file1'));
    }

    /**
     * @expectedException \Webmozart\Puli\Filesystem\FilesystemException
     */
    public function testFailIfNoDirectory()
    {
        new LocalDirectoryResource($this->fixturesDir.'/dir1/file1');
    }

    public function testListEntriesLoadsFilesystemIfNoLoaderAdded()
    {
        $directory = new LocalDirectoryResource($this->fixturesDir.'/dir1');

        $entries = $directory->listEntries();

        $this->assertCount(2, $entries);
        $this->assertInstanceOf('Webmozart\\Puli\\Filesystem\\Resource\\LocalResourceCollection', $entries);
        $this->assertEquals(new LocalFileResource($this->fixturesDir.'/dir1/file1'), $entries['file1']);
        $this->assertEquals(new LocalFileResource($this->fixturesDir.'/dir1/file2'), $entries['file2']);
    }

    public function testListEntriesCorrectsPath()
    {
        $directory = LocalDirectoryResource::forPath('/webmozart/puli', $this->fixturesDir.'/dir1');

        $entries = $directory->listEntries();

        $this->assertCount(2, $entries);
        $this->assertSame('/webmozart/puli/file1', $entries['file1']->getPath());
        $this->assertSame('/webmozart/puli/file2', $entries['file2']->getPath());
    }
}
