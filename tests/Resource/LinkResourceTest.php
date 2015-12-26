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

use Puli\Repository\Resource\Collection\ArrayResourceCollection;
use Puli\Repository\Resource\LinkResource;

/**
 * @author Titouan Galopin <galopintitouan@gmail.com>
 */
class LinkResourceTest extends AbstractResourceTest
{
    protected function createResource($path = null)
    {
        return new LinkResource('/target-path', $path);
    }

    public function testListChildren()
    {
        $file1 = new TestFile('/file1');
        $file2 = new TestFile('/file2');
        $resources = new ArrayResourceCollection(array($file1, $file2));
        $repo = $this->getMock('Puli\Repository\Api\ResourceRepository');

        $repo->expects($this->once())
            ->method('listChildren')
            ->with('/target-path')
            ->will($this->returnValue($resources));

        $resource = $this->createResource('/path');
        $resource->attachTo($repo);

        $children = $resource->listChildren();

        $this->assertInstanceOf('Puli\Repository\Api\ResourceCollection', $children);
        $this->assertEquals(array('file1' => $file1, 'file2' => $file2), $children->toArray());
    }

    public function testListChildrenWithReference()
    {
        $file1 = new TestFile('/file1');
        $file2 = new TestFile('/file2');
        $resources = new ArrayResourceCollection(array($file1, $file2));
        $repo = $this->getMock('Puli\Repository\Api\ResourceRepository');

        $repo->expects($this->once())
            ->method('listChildren')
            // use the repository path, not the reference path
            ->with('/target-path')
            ->will($this->returnValue($resources));

        $resource = $this->createResource('/path');
        $resource->attachTo($repo);

        $reference = $resource->createReference('/reference');

        $children = $reference->listChildren();

        $this->assertInstanceOf('Puli\Repository\Api\ResourceCollection', $children);
        $this->assertEquals(array('file1' => $file1, 'file2' => $file2), $children->toArray());
    }

    public function testGetChild()
    {
        $child = $this->getMock('Puli\Repository\Api\Resource\PuliResource');
        $repo = $this->getMock('Puli\Repository\Api\ResourceRepository');

        $repo->expects($this->once())
            ->method('get')
            ->with('/target-path/file')
            ->will($this->returnValue($child));

        $resource = $this->createResource('/path');
        $resource->attachTo($repo);

        $this->assertSame($child, $resource->getChild('file'));
    }

    public function testGetChildWithReference()
    {
        $child = $this->getMock('Puli\Repository\Api\Resource\PuliResource');
        $repo = $this->getMock('Puli\Repository\Api\ResourceRepository');

        $repo->expects($this->once())
            ->method('get')
            // use the repository path, not the reference path
            ->with('/target-path/file')
            ->will($this->returnValue($child));

        $resource = $this->createResource('/path');
        $resource->attachTo($repo);

        $reference = $resource->createReference('/reference');

        $this->assertSame($child, $reference->getChild('file'));
    }

    public function testHasChild()
    {
        $repo = $this->getMock('Puli\Repository\Api\ResourceRepository');

        $repo->expects($this->once())
            ->method('contains')
            ->with('/target-path/file')
            ->will($this->returnValue('true_or_false'));

        $resource = $this->createResource('/path');
        $resource->attachTo($repo);

        $this->assertSame('true_or_false', $resource->hasChild('file'));
    }

    public function testHasChildWithReference()
    {
        $repo = $this->getMock('Puli\Repository\Api\ResourceRepository');

        $repo->expects($this->once())
            ->method('contains')
            // use the repository path, not the reference path
            ->with('/target-path/file')
            ->will($this->returnValue('true_or_false'));

        $resource = $this->createResource('/path');
        $resource->attachTo($repo);

        $reference = $resource->createReference('/reference');

        $this->assertSame('true_or_false', $reference->hasChild('file'));
    }

    public function testSerializeKeepsTargetPath()
    {
        $resource = $this->createResource('/link-path');

        $deserialized = unserialize(serialize($resource));

        $this->assertEquals($resource->getTargetPath(), $deserialized->getTargetPath());
    }

    /**
     * @expectedException \Puli\Repository\Api\ResourceNotFoundException
     */
    public function testGetTargetFailsIfNoRepository()
    {
        $resource = $this->createResource('/path');
        $resource->getTarget();
    }

    /**
     * @expectedException \Puli\Repository\Api\ResourceNotFoundException
     */
    public function testGetChildFailsIfNoRepository()
    {
        $resource = $this->createResource('/path');
        $resource->getChild('/child');
    }
}
