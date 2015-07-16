<?php

/*
 * This file is part of the puli/repository package.
 *
 * (c) Bernhard Schussek <bschussek@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Puli\Repository\Tests\Resource\Collection;

use PHPUnit_Framework_TestCase;
use Puli\Repository\Resource\Collection\FilesystemResourceCollection;
use Puli\Repository\Resource\DirectoryResource;
use Puli\Repository\Resource\FileResource;
use Puli\Repository\Tests\Resource\TestDirectory;
use Puli\Repository\Tests\Resource\TestFile;

/**
 * @author Bernhard Schussek <bschussek@gmail.com>
 */
class FilesystemResourceCollectionTest extends PHPUnit_Framework_TestCase
{
    private $fixturesDir;

    protected function setUp()
    {
        $this->fixturesDir = __DIR__.'/../Fixtures';
    }

    public function testConstruct()
    {
        $collection = new FilesystemResourceCollection(array(
            $dir = new DirectoryResource($this->fixturesDir.'/dir1'),
            $file = new FileResource($this->fixturesDir.'/file3'),
        ));

        $this->assertCount(2, $collection);
        $this->assertSame(array($dir, $file), $collection->toArray());
        $this->assertSame($dir, $collection->get(0));
        $this->assertSame($file, $collection->get(1));
    }

    /**
     * @expectedException \InvalidArgumentException
     */
    public function testConstructFailsIfNoTraversable()
    {
        new FilesystemResourceCollection('foobar');
    }

    public function testReplace()
    {
        $collection = new FilesystemResourceCollection(array(
            new DirectoryResource($this->fixturesDir.'/dir1'),
        ));

        $collection->replace(array(
            $dir = new DirectoryResource($this->fixturesDir.'/dir2'),
            $file = new FileResource($this->fixturesDir.'/file3'),
        ));

        $this->assertCount(2, $collection);
        $this->assertSame(array($dir, $file), $collection->toArray());
        $this->assertSame($dir, $collection->get(0));
        $this->assertSame($file, $collection->get(1));
    }

    /**
     * @expectedException \InvalidArgumentException
     */
    public function testReplaceFailsIfNoTraversable()
    {
        $collection = new FilesystemResourceCollection();

        $collection->replace('foobar');
    }

    public function testAdd()
    {
        $collection = new FilesystemResourceCollection(array(
            $dir = new DirectoryResource($this->fixturesDir.'/dir1'),
        ));

        $collection->add($file = new FileResource($this->fixturesDir.'/file3'));

        $this->assertCount(2, $collection);
        $this->assertSame(array($dir, $file), $collection->toArray());
        $this->assertSame($dir, $collection->get(0));
        $this->assertSame($file, $collection->get(1));
    }

    public function testGetFilesystemPaths()
    {
        $collection = new FilesystemResourceCollection(array(
            $dir = new DirectoryResource($this->fixturesDir.'/dir1'),
            $file = new FileResource($this->fixturesDir.'/file3'),
        ));

        $this->assertSame(array(
            $dir->getFilesystemPath(),
            $file->getFilesystemPath(),
        ), $collection->getFilesystemPaths());
    }

    public function testGetFilesystemPathsIgnoresNonFilesystemResources()
    {
        $collection = new FilesystemResourceCollection(array(
            $dir = new DirectoryResource($this->fixturesDir.'/dir1'),
            $file = new FileResource($this->fixturesDir.'/file3'),
            new TestFile(),
            new TestDirectory(),
        ));

        $this->assertSame(array(
            $dir->getFilesystemPath(),
            $file->getFilesystemPath(),
        ), $collection->getFilesystemPaths());
    }

    public function testGetFilesystemPathsIgnoresResourcesWithEmptyFilesystemPaths()
    {
        $collection = new FilesystemResourceCollection(array(
            $dir = new DirectoryResource($this->fixturesDir.'/dir1'),
            $file = new FileResource($this->fixturesDir.'/file3'),
            $this->getMock('Puli\Repository\Api\Resource\FilesystemResource'),
        ));

        $this->assertSame(array(
            $dir->getFilesystemPath(),
            $file->getFilesystemPath(),
        ), $collection->getFilesystemPaths());
    }
}
