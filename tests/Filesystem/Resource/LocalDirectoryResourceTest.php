<?php

/*
 * This file is part of the Puli package.
 *
 * (c) Bernhard Schussek <bschussek@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Puli\Repository\Tests\Filesystem\Resource;

use Puli\Repository\Filesystem\Resource\LocalDirectoryResource;
use Puli\Repository\Filesystem\Resource\LocalFileResource;
use Puli\Repository\ResourceRepositoryInterface;
use Puli\Repository\Tests\Resource\AbstractAttachableDirectoryResourceTest;
use Puli\Repository\Tests\Resource\AbstractDirectoryResourceTest;

/**
 * @since  1.0
 * @author Bernhard Schussek <bschussek@gmail.com>
 */
class LocalDirectoryResourceTest extends AbstractAttachableDirectoryResourceTest
{
    private $fixturesDir;

    protected function setUp()
    {
        $this->fixturesDir = realpath(__DIR__.'/Fixtures');
        parent::setUp();
    }

    protected function createDir()
    {
        return new LocalDirectoryResource($this->fixturesDir.'/dir1');
    }

    protected function createAttachedDir(ResourceRepositoryInterface $repo, $path)
    {
        return LocalDirectoryResource::createAttached($repo, $path, $this->fixturesDir.'/dir1');
    }

    /**
     * @expectedException \Puli\Repository\UnsupportedResourceException
     */
    public function testOverrideFailsIfLocalResource()
    {
        $directory = new LocalDirectoryResource($this->fixturesDir.'/dir1');

        $directory->override(new TestLocalResource($this->fixturesDir.'/dir1/file1'));
    }

    /**
     * @expectedException \Puli\Repository\UnsupportedResourceException
     */
    public function testOverrideFailsIfLocalFileResource()
    {
        $directory = new LocalDirectoryResource($this->fixturesDir.'/dir1');

        $directory->override(new LocalFileResource($this->fixturesDir.'/dir1/file1'));
    }

    /**
     * @expectedException \Puli\Repository\Filesystem\FilesystemException
     */
    public function testFailIfNoDirectory()
    {
        new LocalDirectoryResource($this->fixturesDir.'/dir1/file1');
    }

    public function testListEntriesDetached()
    {
        $directory = new LocalDirectoryResource($this->fixturesDir.'/dir1');

        $entries = $directory->listEntries();

        $this->assertCount(2, $entries);
        $this->assertInstanceOf('Puli\Repository\\Filesystem\\Resource\\LocalResourceCollection', $entries);
        $this->assertEquals(new LocalFileResource($this->fixturesDir.'/dir1/file1'), $entries['file1']);
        $this->assertEquals(new LocalFileResource($this->fixturesDir.'/dir1/file2'), $entries['file2']);
    }

    public function testGetDetached()
    {
        $directory = new LocalDirectoryResource($this->fixturesDir.'/dir1');

        $this->assertEquals(new LocalFileResource($this->fixturesDir.'/dir1/file1'), $directory->get('file1'));
    }

    public function testContainsDetached()
    {
        $directory = new LocalDirectoryResource($this->fixturesDir.'/dir1');

        $this->assertTrue($directory->contains('file1'));
        $this->assertTrue($directory->contains('file2'));
        $this->assertTrue($directory->contains('.'));
        $this->assertTrue($directory->contains('..'));
        $this->assertFalse($directory->contains('foobar'));
    }
}
