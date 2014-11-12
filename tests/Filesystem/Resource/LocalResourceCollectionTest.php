<?php

/*
 * This file is part of the Puli package.
 *
 * (c) Bernhard Schussek <bschussek@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Webmozart\Puli\Tests\Filesystem\Resource;

use Webmozart\Puli\Filesystem\Resource\LocalDirectoryResource;
use Webmozart\Puli\Filesystem\Resource\LocalFileResource;
use Webmozart\Puli\Filesystem\Resource\LocalResourceCollection;

/**
 * @since  1.0
 * @author Bernhard Schussek <bschussek@gmail.com>
 */
class LocalResourceCollectionTest extends \PHPUnit_Framework_TestCase
{
    private $fixturesDir;

    protected function setUp()
    {
        $this->fixturesDir = __DIR__.'/Fixtures';
    }

    public function testConstruct()
    {
        $collection = new LocalResourceCollection(array(
            $dir = new LocalDirectoryResource($this->fixturesDir.'/dir1'),
            $file = new LocalFileResource($this->fixturesDir.'/file3'),
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
        new LocalResourceCollection('foobar');
    }

    /**
     * @expectedException \Webmozart\Puli\UnsupportedResourceException
     */
    public function testConstructFailsIfNoLocalResource()
    {
        new LocalResourceCollection(array(
            $this->getMock('Webmozart\Puli\Resource\FileResourceInterface'),
        ));
    }

    public function testReplace()
    {
        $collection = new LocalResourceCollection(array(
            new LocalDirectoryResource($this->fixturesDir.'/dir1'),
        ));

        $collection->replace(array(
            $dir = new LocalDirectoryResource($this->fixturesDir.'/dir2'),
            $file = new LocalFileResource($this->fixturesDir.'/file3'),
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
        $collection = new LocalResourceCollection();

        $collection->replace('foobar');
    }

    /**
     * @expectedException \Webmozart\Puli\UnsupportedResourceException
     */
    public function testReplaceFailsIfNoLocalResource()
    {
        $collection = new LocalResourceCollection();

        $collection->replace(array(
            $this->getMock('Webmozart\Puli\Resource\FileResourceInterface'),
        ));
    }

    public function testAdd()
    {
        $collection = new LocalResourceCollection(array(
            $dir = new LocalDirectoryResource($this->fixturesDir.'/dir1'),
        ));

        $collection->add($file = new LocalFileResource($this->fixturesDir.'/file3'));

        $this->assertCount(2, $collection);
        $this->assertSame(array($dir, $file), $collection->toArray());
        $this->assertSame($dir, $collection->get(0));
        $this->assertSame($file, $collection->get(1));
    }

    /**
     * @expectedException \Webmozart\Puli\UnsupportedResourceException
     */
    public function testAddFailsIfNoLocalResource()
    {
        $collection = new LocalResourceCollection();

        $collection->add($this->getMock('Webmozart\Puli\Resource\FileResourceInterface'));
    }

    public function testGetLocalPaths()
    {
        $collection = new LocalResourceCollection(array(
            $dir = new LocalDirectoryResource($this->fixturesDir.'/dir1'),
            $file = new LocalFileResource($this->fixturesDir.'/file3'),
        ));

        $this->assertEquals(array($dir->getLocalPath(), $file->getLocalPath()), $collection->getLocalPaths());
    }
}
