<?php

/*
 * This file is part of the Puli package.
 *
 * (c) Bernhard Schussek <bschussek@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Puli\Repository\Tests\Resource;

use Puli\Repository\ResourceRepositoryInterface;
use Puli\Repository\Resource\AttachableResourceInterface;
use Puli\Repository\Resource\Collection\ResourceCollection;
use Puli\Repository\Resource\DirectoryResourceInterface;

/**
 * @since  1.0
 * @author Bernhard Schussek <bschussek@gmail.com>
 */
abstract class AbstractAttachableDirectoryResourceTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @return DirectoryResourceInterface|AttachableResourceInterface
     */
    abstract protected function createDir();

    /**
     * @param ResourceRepositoryInterface $repo
     * @param string                      $path
     *
     * @return DirectoryResourceInterface|AttachableResourceInterface
     */
    abstract protected function createAttachedDir(ResourceRepositoryInterface $repo, $path);

    public function testGetPathAndName()
    {
        $repo = $this->getMock('Puli\Repository\ResourceRepositoryInterface');
        $dir = $this->createAttachedDir($repo, '/path/dir');

        $this->assertSame('/path/dir', $dir->getPath());
        $this->assertSame('dir', $dir->getName());

        $dir->detach();

        $this->assertNull($dir->getPath());
        $this->assertNull($dir->getName());
    }

    public function testGetPathAndNameDetached()
    {
        $dir = $this->createDir();

        $this->assertNull($dir->getPath());
        $this->assertNull($dir->getName());
    }

    public function testListEntries()
    {
        $file1 = new TestFile('/file1');
        $file2 = new TestFile('/file2');
        $resources = new ResourceCollection(array($file1, $file2));
        $repo = $this->getMock('Puli\Repository\ResourceRepositoryInterface');

        $repo->expects($this->once())
            ->method('listDirectory')
            ->with('/path')
            ->will($this->returnValue($resources));

        $directory = $this->createAttachedDir($repo, '/path');
        $entries = $directory->listEntries();

        $this->assertInstanceOf('Puli\Repository\Resource\Collection\ResourceCollectionInterface', $entries);
        $this->assertEquals(array('file1' => $file1, 'file2' => $file2), $entries->toArray());
    }

    /**
     * @expectedException \Puli\Repository\Resource\DetachedException
     */
    public function testListEntriesDetached()
    {
        $directory = $this->createDir();

        $directory->listEntries();
    }

    public function testGet()
    {
        $entry = $this->getMock('Puli\Repository\Resource\ResourceInterface');
        $repo = $this->getMock('Puli\Repository\ResourceRepositoryInterface');

        $repo->expects($this->once())
            ->method('get')
            ->with('/path/file')
            ->will($this->returnValue($entry));

        $directory = $this->createAttachedDir($repo, '/path');

        $this->assertSame($entry, $directory->get('file'));
    }

    /**
     * @expectedException \Puli\Repository\Resource\DetachedException
     */
    public function testGetDetached()
    {
        $directory = $this->createDir();

        $directory->get('file');
    }

    public function testContains()
    {
        $repo = $this->getMock('Puli\Repository\ResourceRepositoryInterface');

        $repo->expects($this->once())
            ->method('contains')
            ->with('/path/file')
            ->will($this->returnValue('true_or_false'));

        $directory = $this->createAttachedDir($repo, '/path');

        $this->assertSame('true_or_false', $directory->contains('file'));
    }

    /**
     * @expectedException \Puli\Repository\Resource\DetachedException
     */
    public function testContainsDetached()
    {
        $directory = $this->createDir();

        $directory->contains('file');
    }

    public function testDetach()
    {
        $repo = $this->getMock('Puli\Repository\ResourceRepositoryInterface');
        $directory = $this->createAttachedDir($repo, '/path');

        $this->assertSame('/path', $directory->getPath());

        $directory->detach();

        $this->assertNull($directory->getPath());
    }
}
