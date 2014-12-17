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

use Puli\Repository\Resource\Resource;
use Puli\Repository\ResourceRepository;

/**
 * @since  1.0
 * @author Bernhard Schussek <bschussek@gmail.com>
 */
abstract class AbstractResourceTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var \PHPUnit_Framework_MockObject_MockObject|ResourceRepository
     */
    protected $repo;

    /**
     * @param string|null $path
     *
     * @return Resource
     */
    abstract protected function createResource($path = null);

    protected function setUp()
    {
        $this->repo = $this->getMock('Puli\Repository\ResourceRepository');
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
        $repo2 = $this->getMock('Puli\Repository\ResourceRepository');

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
        $resource->attachTo($this->repo, '/path/to/resource');

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

        $reference = $resource->createReference('/path/to/reference');
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
        $resource = $this->createResource('/path/to/resource');

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
        $resource->attachTo($this->repo, '/path/to/resource');

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
        $resource = $this->createResource('/path/to/resource');
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
        $resource->attachTo($this->repo, '/path/to/resource');
        $reference = $resource->createReference('/path/to/reference');

        $deserialized = unserialize(serialize($reference));

        $this->assertSame('/path/to/reference', $deserialized->getPath());
        $this->assertSame('reference', $deserialized->getName());
        $this->assertSame('/path/to/resource', $deserialized->getRepositoryPath());
        $this->assertNull($deserialized->getRepository());
        $this->assertFalse($deserialized->isAttached());
        $this->assertTrue($deserialized->isReference());
    }
}
