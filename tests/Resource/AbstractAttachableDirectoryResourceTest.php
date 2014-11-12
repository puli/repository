<?php

/*
 * This file is part of the Puli package.
 *
 * (c) Bernhard Schussek <bschussek@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Webmozart\Puli\Tests\Resource;

use Webmozart\Puli\Resource\AttachableResourceInterface;
use Webmozart\Puli\Resource\Collection\ResourceCollection;
use Webmozart\Puli\Resource\DirectoryResourceInterface;
use Webmozart\Puli\ResourceRepositoryInterface;

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

    public function testListEntries()
    {
        $file1 = new TestFile('/file1');
        $file2 = new TestFile('/file2');
        $resources = new ResourceCollection(array($file1, $file2));
        $repo = $this->getMock('Webmozart\Puli\ResourceRepositoryInterface');

        $repo->expects($this->once())
            ->method('find')
            ->with('/path/*')
            ->will($this->returnValue($resources));

        $directory = $this->createAttachedDir($repo, '/path');
        $entries = $directory->listEntries();

        $this->assertInstanceOf('Webmozart\Puli\Resource\Collection\ResourceCollectionInterface', $entries);
        $this->assertEquals(array('file1' => $file1, 'file2' => $file2), $entries->toArray());
    }

    /**
     * @expectedException \Webmozart\Puli\Resource\DetachedException
     */
    public function testListEntriesDetached()
    {
        $directory = $this->createDir();

        $directory->listEntries();
    }

    public function testGet()
    {
        $entry = $this->getMock('Webmozart\Puli\Resource\ResourceInterface');
        $repo = $this->getMock('Webmozart\Puli\ResourceRepositoryInterface');

        $repo->expects($this->once())
            ->method('get')
            ->with('/path/file')
            ->will($this->returnValue($entry));

        $directory = $this->createAttachedDir($repo, '/path');

        $this->assertSame($entry, $directory->get('file'));
    }

    /**
     * @expectedException \Webmozart\Puli\Resource\DetachedException
     */
    public function testGetDetached()
    {
        $directory = $this->createDir();

        $directory->get('file');
    }

    public function testContains()
    {
        $repo = $this->getMock('Webmozart\Puli\ResourceRepositoryInterface');

        $repo->expects($this->once())
            ->method('contains')
            ->with('/path/file')
            ->will($this->returnValue('true_or_false'));

        $directory = $this->createAttachedDir($repo, '/path');

        $this->assertSame('true_or_false', $directory->contains('file'));
    }

    /**
     * @expectedException \Webmozart\Puli\Resource\DetachedException
     */
    public function testContainsDetached()
    {
        $directory = $this->createDir();

        $directory->contains('file');
    }

    public function testDetach()
    {
        $repo = $this->getMock('Webmozart\Puli\ResourceRepositoryInterface');
        $directory = $this->createAttachedDir($repo, '/path');

        $this->assertSame('/path', $directory->getPath());

        $directory->detach();

        $this->assertNull($directory->getPath());
    }
}
