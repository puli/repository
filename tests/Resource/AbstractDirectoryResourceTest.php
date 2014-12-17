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

/**
 * @since  1.0
 * @author Bernhard Schussek <bschussek@gmail.com>
 */
abstract class AbstractDirectoryResourceTest extends AbstractResourceTest
{
    public function testListEntries()
    {
        $file1 = new TestFile('/file1');
        $file2 = new TestFile('/file2');
        $resources = new ArrayResourceCollection(array($file1, $file2));
        $repo = $this->getMock('Puli\Repository\ResourceRepository');

        $repo->expects($this->once())
            ->method('listDirectory')
            ->with('/path')
            ->will($this->returnValue($resources));

        $directory = $this->createResource('/path');
        $directory->attachTo($repo);

        $entries = $directory->listEntries();

        $this->assertInstanceOf('Puli\Repository\Resource\Collection\ResourceCollection', $entries);
        $this->assertEquals(array('file1' => $file1, 'file2' => $file2), $entries->toArray());
    }

    public function testListEntriesWithReference()
    {
        $file1 = new TestFile('/file1');
        $file2 = new TestFile('/file2');
        $resources = new ArrayResourceCollection(array($file1, $file2));
        $repo = $this->getMock('Puli\Repository\ResourceRepository');

        $repo->expects($this->once())
            ->method('listDirectory')
            // use the repository path, not the reference path
            ->with('/path')
            ->will($this->returnValue($resources));

        $directory = $this->createResource('/path');
        $directory->attachTo($repo);

        $reference = $directory->createReference('/reference');

        $entries = $reference->listEntries();

        $this->assertInstanceOf('Puli\Repository\Resource\Collection\ResourceCollection', $entries);
        $this->assertEquals(array('file1' => $file1, 'file2' => $file2), $entries->toArray());
    }

    /**
     * @expectedException \Puli\Repository\Resource\DetachedException
     */
    public function testListEntriesDetached()
    {
        $directory = $this->createResource();

        $directory->listEntries();
    }

    public function testGet()
    {
        $entry = $this->getMock('Puli\Repository\Resource\Resource');
        $repo = $this->getMock('Puli\Repository\ResourceRepository');

        $repo->expects($this->once())
            ->method('get')
            ->with('/path/file')
            ->will($this->returnValue($entry));

        $directory = $this->createResource('/path');
        $directory->attachTo($repo);

        $this->assertSame($entry, $directory->get('file'));
    }

    public function testGetWithReference()
    {
        $entry = $this->getMock('Puli\Repository\Resource\Resource');
        $repo = $this->getMock('Puli\Repository\ResourceRepository');

        $repo->expects($this->once())
            ->method('get')
            // use the repository path, not the reference path
            ->with('/path/file')
            ->will($this->returnValue($entry));

        $directory = $this->createResource('/path');
        $directory->attachTo($repo);

        $reference = $directory->createReference('/reference');

        $this->assertSame($entry, $reference->get('file'));
    }

    /**
     * @expectedException \Puli\Repository\Resource\DetachedException
     */
    public function testGetDetached()
    {
        $directory = $this->createResource();

        $directory->get('file');
    }

    public function testContains()
    {
        $repo = $this->getMock('Puli\Repository\ResourceRepository');

        $repo->expects($this->once())
            ->method('contains')
            ->with('/path/file')
            ->will($this->returnValue('true_or_false'));

        $directory = $this->createResource('/path');
        $directory->attachTo($repo);

        $this->assertSame('true_or_false', $directory->contains('file'));
    }

    public function testContainsWithReference()
    {
        $repo = $this->getMock('Puli\Repository\ResourceRepository');

        $repo->expects($this->once())
            ->method('contains')
            // use the repository path, not the reference path
            ->with('/path/file')
            ->will($this->returnValue('true_or_false'));

        $directory = $this->createResource('/path');
        $directory->attachTo($repo);

        $reference = $directory->createReference('/reference');

        $this->assertSame('true_or_false', $reference->contains('file'));
    }

    /**
     * @expectedException \Puli\Repository\Resource\DetachedException
     */
    public function testContainsDetached()
    {
        $directory = $this->createResource();

        $directory->contains('file');
    }
}
