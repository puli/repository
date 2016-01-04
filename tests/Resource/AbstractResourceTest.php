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

use PHPUnit_Framework_MockObject_MockObject;
use PHPUnit_Framework_TestCase;
use Puli\Repository\Api\ChangeStream\VersionList;
use Puli\Repository\Api\Resource\PuliResource;
use Puli\Repository\Api\ResourceRepository;
use Puli\Repository\Resource\Collection\ArrayResourceCollection;

/**
 * @author Bernhard Schussek <bschussek@gmail.com>
 */
abstract class AbstractResourceTest extends PHPUnit_Framework_TestCase
{
    /**
     * @var PHPUnit_Framework_MockObject_MockObject|ResourceRepository
     */
    protected $repo;

    /**
     * @param string|null $path
     *
     * @return PuliResource
     */
    abstract protected function createResource($path = null);

    protected function setUp()
    {
        $this->repo = $this->getMock('Puli\Repository\Api\ResourceRepository');
    }

    public function testCreate()
    {
        $resource = $this->createResource();

        $this->assertNull($resource->getPath());
        $this->assertNull($resource->getName());
        $this->assertNull($resource->getRepositoryPath());
        $this->assertNull($resource->getRepository());
        $this->assertFalse($resource->isAttached());
        $this->assertFalse($resource->isReference());
    }

    public function testCreateWithPath()
    {
        $resource = $this->createResource('/path/to/resource');

        $this->assertSame('/path/to/resource', $resource->getPath());
        $this->assertSame('resource', $resource->getName());
        $this->assertSame('/path/to/resource', $resource->getRepositoryPath());
        $this->assertNull($resource->getRepository());
        $this->assertFalse($resource->isAttached());
        $this->assertFalse($resource->isReference());
    }

    public function testAttach()
    {
        $resource = $this->createResource();
        $resource->attachTo($this->repo);

        $this->assertNull($resource->getPath());
        $this->assertNull($resource->getName());
        $this->assertNull($resource->getRepositoryPath());
        $this->assertSame($this->repo, $resource->getRepository());
        $this->assertTrue($resource->isAttached());
        $this->assertFalse($resource->isReference());
    }

    public function testAttachDoesNotChangePath()
    {
        $resource = $this->createResource('/path/to/resource');
        $resource->attachTo($this->repo);

        $this->assertSame('/path/to/resource', $resource->getPath());
        $this->assertSame('resource', $resource->getName());
        $this->assertSame('/path/to/resource', $resource->getRepositoryPath());
        $this->assertSame($this->repo, $resource->getRepository());
        $this->assertTrue($resource->isAttached());
        $this->assertFalse($resource->isReference());
    }

    public function testAttachSetsPathIfGiven()
    {
        $resource = $this->createResource('/path/to/resource');
        $resource->attachTo($this->repo, '/path/to/attached');

        $this->assertSame('/path/to/attached', $resource->getPath());
        $this->assertSame('attached', $resource->getName());
        $this->assertSame('/path/to/attached', $resource->getRepositoryPath());
        $this->assertSame($this->repo, $resource->getRepository());
        $this->assertTrue($resource->isAttached());
        $this->assertFalse($resource->isReference());
    }

    public function testReattach()
    {
        $repo2 = $this->getMock('Puli\Repository\Api\ResourceRepository');

        $resource = $this->createResource();
        $resource->attachTo($this->repo, '/path/to/resource');
        $resource->attachTo($repo2, '/path/to/reattached');

        $this->assertSame('/path/to/reattached', $resource->getPath());
        $this->assertSame('reattached', $resource->getName());
        $this->assertSame('/path/to/reattached', $resource->getRepositoryPath());
        $this->assertSame($repo2, $resource->getRepository());
        $this->assertTrue($resource->isAttached());
        $this->assertFalse($resource->isReference());
    }

    public function testDetach()
    {
        $resource = $this->createResource();
        $resource->attachTo($this->repo);
        $resource->detach();

        $this->assertNull($resource->getPath());
        $this->assertNull($resource->getName());
        $this->assertNull($resource->getRepositoryPath());
        $this->assertNull($resource->getRepository());
        $this->assertFalse($resource->isAttached());
        $this->assertFalse($resource->isReference());
    }

    public function testDetachKeepsPath()
    {
        $resource = $this->createResource();
        $resource->attachTo($this->repo, '/path/to/resource');
        $resource->detach();

        $this->assertSame('/path/to/resource', $resource->getPath());
        $this->assertSame('resource', $resource->getName());
        $this->assertSame('/path/to/resource', $resource->getRepositoryPath());
        $this->assertNull($resource->getRepository());
        $this->assertFalse($resource->isAttached());
        $this->assertFalse($resource->isReference());
    }

    public function testCreateReferenceToDetachedResource()
    {
        $resource = $this->createResource();

        $reference = $resource->createReference('/path/to/reference');

        $this->assertNull($resource->getPath());
        $this->assertNull($resource->getName());
        $this->assertNull($resource->getRepositoryPath());
        $this->assertNull($resource->getRepository());
        $this->assertFalse($resource->isAttached());
        $this->assertFalse($resource->isReference());

        $this->assertSame('/path/to/reference', $reference->getPath());
        $this->assertSame('reference', $reference->getName());
        $this->assertNull($reference->getRepositoryPath());
        $this->assertNull($reference->getRepository());
        $this->assertFalse($reference->isAttached());
        $this->assertTrue($reference->isReference());
    }

    public function testCreateReferenceToDetachedResourceWithPath()
    {
        $resource = $this->createResource('/path/to/resource');

        $reference = $resource->createReference('/path/to/reference');

        $this->assertSame('/path/to/resource', $resource->getPath());
        $this->assertSame('resource', $resource->getName());
        $this->assertSame('/path/to/resource', $resource->getRepositoryPath());
        $this->assertNull($resource->getRepository());
        $this->assertFalse($resource->isAttached());
        $this->assertFalse($resource->isReference());

        $this->assertSame('/path/to/reference', $reference->getPath());
        $this->assertSame('reference', $reference->getName());
        $this->assertSame('/path/to/resource', $reference->getRepositoryPath());
        $this->assertNull($reference->getRepository());
        $this->assertFalse($reference->isAttached());
        $this->assertTrue($reference->isReference());
    }

    public function testCreateReferenceToAttachedResource()
    {
        $resource = $this->createResource();
        $resource->attachTo($this->repo, '/path/to/resource', 3);

        $reference = $resource->createReference('/path/to/reference');

        $this->assertSame('/path/to/resource', $resource->getPath());
        $this->assertSame('resource', $resource->getName());
        $this->assertSame('/path/to/resource', $resource->getRepositoryPath());
        $this->assertSame($this->repo, $resource->getRepository());
        $this->assertTrue($resource->isAttached());
        $this->assertFalse($resource->isReference());

        $this->assertSame('/path/to/reference', $reference->getPath());
        $this->assertSame('reference', $reference->getName());
        $this->assertSame('/path/to/resource', $reference->getRepositoryPath());
        $this->assertSame($this->repo, $reference->getRepository());
        $this->assertTrue($reference->isAttached());
        $this->assertTrue($reference->isReference());
    }

    public function testAttachDetachedReference()
    {
        $resource = $this->createResource();

        $reference = $resource->createReference('/path/to/reference', 3);
        $detached = clone $reference;
        $reference->attachTo($this->repo);
        $attached = clone $reference;

        $this->assertSame('/path/to/reference', $reference->getPath());
        $this->assertSame('reference', $reference->getName());
        $this->assertNull($reference->getRepositoryPath());
        $this->assertSame($this->repo, $reference->getRepository());
        $this->assertTrue($reference->isAttached());
        $this->assertTrue($reference->isReference());

        $reference->detach();

        $this->assertEquals($detached, $reference);

        $reference->attachTo($this->repo);

        $this->assertEquals($attached, $reference);
    }

    public function testAttachDetachedReferenceWithPath()
    {
        $resource = $this->createResource();

        $reference = $resource->createReference('/path/to/reference');
        $reference->attachTo($this->repo, '/path/to/attached');

        // References are dereferenced when a path is passed to attachTo()
        $this->assertSame('/path/to/attached', $reference->getPath());
        $this->assertSame('attached', $reference->getName());
        $this->assertSame('/path/to/attached', $reference->getRepositoryPath());
        $this->assertSame($this->repo, $reference->getRepository());
        $this->assertTrue($reference->isAttached());
        $this->assertFalse($reference->isReference());
    }

    public function testReattachAttachedReference()
    {
        $resource = $this->createResource();
        $resource->attachTo($this->repo, '/path/to/resource');

        $reference = $resource->createReference('/path/to/reference');
        $detached = clone $reference;
        $detached->detach();
        $reference->attachTo($this->repo);
        $attached = clone $reference;

        $this->assertSame('/path/to/reference', $reference->getPath());
        $this->assertSame('reference', $reference->getName());
        $this->assertSame('/path/to/resource', $reference->getRepositoryPath());
        $this->assertSame($this->repo, $reference->getRepository());
        $this->assertTrue($reference->isAttached());
        $this->assertTrue($reference->isReference());

        $reference->detach();

        $this->assertEquals($detached, $reference);

        $reference->attachTo($this->repo);

        $this->assertEquals($attached, $reference);
    }

    public function testReattachAttachedReferenceWithPath()
    {
        $resource = $this->createResource();
        $resource->attachTo($this->repo, '/path/to/resource');

        $reference = $resource->createReference('/path/to/reference');
        $reference->attachTo($this->repo, '/path/to/attached');

        // References are dereferenced when a path is passed to attachTo()
        $this->assertSame('/path/to/attached', $reference->getPath());
        $this->assertSame('attached', $reference->getName());
        $this->assertSame('/path/to/attached', $reference->getRepositoryPath());
        $this->assertSame($this->repo, $reference->getRepository());
        $this->assertTrue($reference->isAttached());
        $this->assertFalse($reference->isReference());
    }

    public function testSerializeDetachedResource()
    {
        $resource = $this->createResource();

        $deserialized = unserialize(serialize($resource));

        $this->assertNull($deserialized->getPath());
        $this->assertNull($deserialized->getName());
        $this->assertNull($deserialized->getRepositoryPath());
        $this->assertNull($deserialized->getRepository());
        $this->assertFalse($deserialized->isAttached());
        $this->assertFalse($deserialized->isReference());
    }

    public function testSerializeDetachedResourceWithPath()
    {
        $resource = $this->createResource('/path/to/resource', 3);

        $deserialized = unserialize(serialize($resource));

        $this->assertSame('/path/to/resource', $deserialized->getPath());
        $this->assertSame('resource', $deserialized->getName());
        $this->assertSame('/path/to/resource', $deserialized->getRepositoryPath());
        $this->assertNull($deserialized->getRepository());
        $this->assertFalse($deserialized->isAttached());
        $this->assertFalse($deserialized->isReference());
    }

    public function testSerializeAttachedResourceDetachesResource()
    {
        $resource = $this->createResource();
        $resource->attachTo($this->repo, '/path/to/resource', 3);

        $deserialized = unserialize(serialize($resource));

        $this->assertSame('/path/to/resource', $deserialized->getPath());
        $this->assertSame('resource', $deserialized->getName());
        $this->assertSame('/path/to/resource', $deserialized->getRepositoryPath());
        $this->assertNull($deserialized->getRepository());
        $this->assertFalse($deserialized->isAttached());
        $this->assertFalse($deserialized->isReference());
    }

    public function testSerializeDetachedReference()
    {
        $resource = $this->createResource();
        $reference = $resource->createReference('/path/to/reference');

        $deserialized = unserialize(serialize($reference));

        $this->assertSame('/path/to/reference', $deserialized->getPath());
        $this->assertSame('reference', $deserialized->getName());
        $this->assertNull($deserialized->getRepositoryPath());
        $this->assertNull($deserialized->getRepository());
        $this->assertFalse($deserialized->isAttached());
        $this->assertTrue($deserialized->isReference());
    }

    public function testSerializeDetachedReferenceWithPath()
    {
        $resource = $this->createResource('/path/to/resource', 3);
        $reference = $resource->createReference('/path/to/reference');

        $deserialized = unserialize(serialize($reference));

        $this->assertSame('/path/to/reference', $deserialized->getPath());
        $this->assertSame('reference', $deserialized->getName());
        $this->assertSame('/path/to/resource', $deserialized->getRepositoryPath());
        $this->assertNull($deserialized->getRepository());
        $this->assertFalse($deserialized->isAttached());
        $this->assertTrue($deserialized->isReference());
    }

    public function testSerializeAttachedReferenceDetachesReference()
    {
        $resource = $this->createResource();
        $resource->attachTo($this->repo, '/path/to/resource', 3);
        $reference = $resource->createReference('/path/to/reference');

        $deserialized = unserialize(serialize($reference));

        $this->assertSame('/path/to/reference', $deserialized->getPath());
        $this->assertSame('reference', $deserialized->getName());
        $this->assertSame('/path/to/resource', $deserialized->getRepositoryPath());
        $this->assertNull($deserialized->getRepository());
        $this->assertFalse($deserialized->isAttached());
        $this->assertTrue($deserialized->isReference());
    }

    public function testListChildren()
    {
        $file1 = new TestFile('/file1');
        $file2 = new TestFile('/file2');
        $resources = new ArrayResourceCollection(array($file1, $file2));
        $repo = $this->getMock('Puli\Repository\Api\ResourceRepository');

        $repo->expects($this->once())
            ->method('listChildren')
            ->with('/path')
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
            ->with('/path')
            ->will($this->returnValue($resources));

        $resource = $this->createResource('/path');
        $resource->attachTo($repo);

        $reference = $resource->createReference('/reference');

        $children = $reference->listChildren();

        $this->assertInstanceOf('Puli\Repository\Api\ResourceCollection', $children);
        $this->assertEquals(array('file1' => $file1, 'file2' => $file2), $children->toArray());
    }

    public function testListChildrenDetached()
    {
        $resource = $this->createResource();

        $children = $resource->listChildren();

        $this->assertInstanceOf('Puli\Repository\Api\ResourceCollection', $children);
        $this->assertEquals(array(), $children->toArray());
    }

    public function testGetChild()
    {
        $child = $this->getMock('Puli\Repository\Api\Resource\PuliResource');
        $repo = $this->getMock('Puli\Repository\Api\ResourceRepository');

        $repo->expects($this->once())
            ->method('get')
            ->with('/path/file')
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
            ->with('/path/file')
            ->will($this->returnValue($child));

        $resource = $this->createResource('/path');
        $resource->attachTo($repo);

        $reference = $resource->createReference('/reference');

        $this->assertSame($child, $reference->getChild('file'));
    }

    /**
     * @expectedException \Puli\Repository\Api\ResourceNotFoundException
     */
    public function testGetChildDetached()
    {
        $resource = $this->createResource();

        $resource->getChild('file');
    }

    public function testHasChild()
    {
        $repo = $this->getMock('Puli\Repository\Api\ResourceRepository');

        $repo->expects($this->once())
            ->method('contains')
            ->with('/path/file')
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
            ->with('/path/file')
            ->will($this->returnValue('true_or_false'));

        $resource = $this->createResource('/path');
        $resource->attachTo($repo);

        $reference = $resource->createReference('/reference');

        $this->assertSame('true_or_false', $reference->hasChild('file'));
    }

    public function testHasChildDetached()
    {
        $resource = $this->createResource();

        $this->assertFalse($resource->hasChild('file'));
    }

    public function testHasChildren()
    {
        $repo = $this->getMock('Puli\Repository\Api\ResourceRepository');

        $repo->expects($this->once())
            ->method('hasChildren')
            ->with('/path')
            ->will($this->returnValue('true_or_false'));

        $resource = $this->createResource('/path');
        $resource->attachTo($repo);

        $this->assertSame('true_or_false', $resource->hasChildren());
    }

    public function testHasChildrenWithReference()
    {
        $repo = $this->getMock('Puli\Repository\Api\ResourceRepository');

        $repo->expects($this->once())
            ->method('hasChildren')
            // use the repository path, not the reference path
            ->with('/path')
            ->will($this->returnValue('true_or_false'));

        $resource = $this->createResource('/path');
        $resource->attachTo($repo);

        $reference = $resource->createReference('/reference');

        $this->assertSame('true_or_false', $reference->hasChildren());
    }

    public function testHasChildrenDetached()
    {
        $resource = $this->createResource();

        $this->assertFalse($resource->hasChildren());
    }

    public function testGetVersions()
    {
        $repo = $this->getMock('Puli\Repository\Api\ResourceRepository');

        $resource = $this->createResource('/path');
        $resource->attachTo($repo);

        $versions = new VersionList('/path', array($resource));

        $repo->expects($this->once())
            ->method('getVersions')
            // use the repository path, not the reference path
            ->with('/path')
            ->will($this->returnValue($versions));

        $this->assertSame($versions, $resource->getVersions());
    }

    public function testGetVersionsDetached()
    {
        $resource = $this->createResource('/path');

        $versions = new VersionList('/path', array($resource));

        $this->assertEquals($versions, $resource->getVersions());
    }
}
