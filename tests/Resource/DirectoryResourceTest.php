<?php

/*
 * This file is part of the puli/repository package.
 *
 * (c) Bernhard Schussek <bschussek@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Puli\Repository\Tests\Resource;

use Puli\Repository\Resource\DirectoryResource;
use Puli\Repository\Resource\FileResource;
use Symfony\Component\Filesystem\Filesystem;
use Webmozart\Glob\Test\TestUtil;

/**
 * @author Bernhard Schussek <bschussek@gmail.com>
 */
class DirectoryResourceTest extends AbstractFilesystemResourceTest
{
    private $fixturesDir;

    private $tempEmptyDir;

    protected function setUp()
    {
        parent::setUp();

        $this->tempEmptyDir = TestUtil::makeTempDir('puli-repository', __CLASS__);
        $this->fixturesDir = realpath(__DIR__.'/Fixtures');
    }

    protected function tearDown()
    {
        $filesystem = new Filesystem();
        $filesystem->remove($this->tempEmptyDir);

        parent::tearDown();
    }

    protected function createFilesystemResource($filesystemPath, $path = null)
    {
        return new DirectoryResource($filesystemPath, $path);
    }

    protected function getValidFilesystemPath()
    {
        return $this->fixturesDir.'/dir1';
    }

    protected function getValidFilesystemPath2()
    {
        return $this->fixturesDir.'/dir2';
    }

    protected function getValidFilesystemPath3()
    {
        return $this->fixturesDir.'/empty';
    }

    public function getInvalidFilesystemPaths()
    {
        // setUp() has not yet been called in the data provider
        $fixturesDir = realpath(__DIR__.'/Fixtures');

        return array(
            // No directory
            array($fixturesDir.'/file3'),
            // Does not exist
            array($fixturesDir.'/foobar'),
        );
    }

    /**
     * @expectedException \InvalidArgumentException
     */
    public function testFailIfNoDirectory()
    {
        new DirectoryResource($this->fixturesDir.'/dir1/file1');
    }

    public function testListChildrenDetached()
    {
        $resource = new DirectoryResource($this->fixturesDir.'/dir1');

        $children = $resource->listChildren();

        $this->assertCount(2, $children);
        $this->assertInstanceOf('Puli\Repository\Resource\Collection\FilesystemResourceCollection', $children);
        $this->assertEquals(new FileResource($this->fixturesDir.'/dir1/file1'), $children['file1']);
        $this->assertEquals(new FileResource($this->fixturesDir.'/dir1/file2'), $children['file2']);
    }

    public function testGetChildDetached()
    {
        $resource = new DirectoryResource($this->fixturesDir.'/dir1');

        $this->assertEquals(new FileResource($this->fixturesDir.'/dir1/file1'), $resource->getChild('file1'));
    }

    public function testHasChildDetached()
    {
        $resource = new DirectoryResource($this->fixturesDir.'/dir1');

        $this->assertTrue($resource->hasChild('file1'));
        $this->assertTrue($resource->hasChild('file2'));
        $this->assertTrue($resource->hasChild('.'));
        $this->assertTrue($resource->hasChild('..'));
        $this->assertFalse($resource->hasChild('foobar'));
    }

    public function testHasChildrenDetached()
    {
        $resource = new DirectoryResource($this->fixturesDir.'/dir1');

        $this->assertTrue($resource->hasChildren());

        $resource = new DirectoryResource($this->tempEmptyDir);

        $this->assertFalse($resource->hasChildren());
    }
}
