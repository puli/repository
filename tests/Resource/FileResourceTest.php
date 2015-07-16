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

use Puli\Repository\Resource\FileResource;
/**
 * @author Bernhard Schussek <bschussek@gmail.com>
 */
class FileResourceTest extends AbstractFilesystemResourceTest
{
    private $fixturesDir;

    protected function setUp()
    {
        parent::setUp();

        $this->fixturesDir = realpath(__DIR__.'/Fixtures');
    }

    protected function createFilesystemResource($resourcesystemPath, $path = null)
    {
        return new FileResource($resourcesystemPath, $path);
    }

    protected function getValidFilesystemPath()
    {
        return $this->fixturesDir.'/dir1/file1';
    }

    protected function getValidFilesystemPath2()
    {
        return $this->fixturesDir.'/dir1/file2';
    }

    protected function getValidFilesystemPath3()
    {
        return $this->fixturesDir.'/dir2/file1';
    }

    public function getInvalidFilesystemPaths()
    {
        // setUp() has not yet been called in the data provider
        $fixturesDir = realpath(__DIR__.'/Fixtures');

        return array(
            // Not a file
            array($fixturesDir.'/dir1'),
            // Does not exist
            array($fixturesDir.'/foobar'),
        );
    }

    public function testGetContents()
    {
        $resource = new FileResource($this->fixturesDir.'/dir1/file1');

        $this->assertSame(file_get_contents($resource->getFilesystemPath()), $resource->getBody());
    }

    public function testListChildren()
    {
        $repo = $this->getMock('Puli\Repository\Api\ResourceRepository');

        $repo->expects($this->never())
            ->method('listChildren');

        $resource = new FileResource($this->fixturesDir.'/dir1/file1');
        $resource->attachTo($repo);

        $children = $resource->listChildren();

        $this->assertInstanceOf('Puli\Repository\Api\ResourceCollection', $children);
        $this->assertEquals(array(), $children->toArray());
    }

    public function testListChildrenWithReference()
    {
        $repo = $this->getMock('Puli\Repository\Api\ResourceRepository');

        $repo->expects($this->never())
            ->method('listChildren');

        $resource = new FileResource($this->fixturesDir.'/dir1/file1');
        $resource->attachTo($repo);

        $reference = $resource->createReference('/reference');

        $children = $reference->listChildren();

        $this->assertInstanceOf('Puli\Repository\Api\ResourceCollection', $children);
        $this->assertEquals(array(), $children->toArray());
    }

    public function testListChildrenDetached()
    {
        $resource = new FileResource($this->fixturesDir.'/dir1/file1');

        $children = $resource->listChildren();

        $this->assertInstanceOf('Puli\Repository\Api\ResourceCollection', $children);
        $this->assertEquals(array(), $children->toArray());
    }

    /**
     * @expectedException \Puli\Repository\Api\ResourceNotFoundException
     */
    public function testGetChild()
    {
        $repo = $this->getMock('Puli\Repository\Api\ResourceRepository');

        $repo->expects($this->never())
            ->method('get');

        $resource = new FileResource($this->fixturesDir.'/dir1/file1');
        $resource->attachTo($repo);

        $resource->getChild('file');
    }

    /**
     * @expectedException \Puli\Repository\Api\ResourceNotFoundException
     */
    public function testGetChildWithReference()
    {
        $repo = $this->getMock('Puli\Repository\Api\ResourceRepository');

        $repo->expects($this->never())
            ->method('get');

        $resource = new FileResource($this->fixturesDir.'/dir1/file1');
        $resource->attachTo($repo);

        $reference = $resource->createReference('/reference');

        $reference->getChild('file');
    }

    /**
     * @expectedException \Puli\Repository\Api\ResourceNotFoundException
     */
    public function testGetChildDetached()
    {
        $resource = new FileResource($this->fixturesDir.'/dir1/file1');

        $resource->getChild('file');
    }

    public function testHasChild()
    {
        $repo = $this->getMock('Puli\Repository\Api\ResourceRepository');

        $repo->expects($this->never())
            ->method('contains');

        $resource = new FileResource($this->fixturesDir.'/dir1/file1');
        $resource->attachTo($repo);

        $this->assertFalse($resource->hasChild('file'));
    }

    public function testHasChildWithReference()
    {
        $repo = $this->getMock('Puli\Repository\Api\ResourceRepository');

        $repo->expects($this->never())
            ->method('contains');

        $resource = new FileResource($this->fixturesDir.'/dir1/file1');
        $resource->attachTo($repo);

        $reference = $resource->createReference('/reference');

        $this->assertFalse($reference->hasChild('file'));
    }

    public function testHasChildDetached()
    {
        $resource = new FileResource($this->fixturesDir.'/dir1/file1');

        $this->assertFalse($resource->hasChild('file'));
    }

    public function testHasChildren()
    {
        $repo = $this->getMock('Puli\Repository\Api\ResourceRepository');

        $repo->expects($this->never())
            ->method('hasChildren');

        $resource = new FileResource($this->fixturesDir.'/dir1/file1');
        $resource->attachTo($repo);

        $this->assertFalse($resource->hasChildren());
    }

    public function testHasChildrenWithReference()
    {
        $repo = $this->getMock('Puli\Repository\Api\ResourceRepository');

        $repo->expects($this->never())
            ->method('hasChildren');

        $resource = new FileResource($this->fixturesDir.'/dir1/file1');
        $resource->attachTo($repo);

        $reference = $resource->createReference('/reference');

        $this->assertFalse($reference->hasChildren());
    }

    public function testHasChildrenDetached()
    {
        $resource = new FileResource($this->fixturesDir.'/dir1/file1');

        $this->assertFalse($resource->hasChildren());
    }
}
