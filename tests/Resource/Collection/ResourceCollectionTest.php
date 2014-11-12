<?php

/*
 * This file is part of the Puli package.
 *
 * (c) Bernhard Schussek <bschussek@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Webmozart\Puli\Tests\Resource\Collection;

use Webmozart\Puli\Resource\Collection\ResourceCollection;

/**
 * @since  1.0
 * @author Bernhard Schussek <bschussek@gmail.com>
 */
class ResourceCollectionTest extends \PHPUnit_Framework_TestCase
{
    public function testConstruct()
    {
        $collection = new ResourceCollection(array(
            $dir = $this->getMock('Webmozart\Puli\Resource\DirectoryResourceInterface'),
            $file = $this->getMock('Webmozart\Puli\Resource\FileResourceInterface'),
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
        new ResourceCollection('foobar');
    }

    /**
     * @expectedException \Webmozart\Puli\UnsupportedResourceException
     */
    public function testConstructFailsIfNoResource()
    {
        new ResourceCollection(array(
            'foobar',
        ));
    }

    public function testReplace()
    {
        $collection = new ResourceCollection(array(
            $this->getMock('Webmozart\Puli\Resource\DirectoryResourceInterface'),
            $this->getMock('Webmozart\Puli\Resource\DirectoryResourceInterface'),
        ));

        $collection->replace(array(
            $dir = $this->getMock('Webmozart\Puli\Resource\DirectoryResourceInterface'),
            $file = $this->getMock('Webmozart\Puli\Resource\FileResourceInterface'),
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
        $collection = new ResourceCollection();

        $collection->replace('foobar');
    }

    /**
     * @expectedException \Webmozart\Puli\UnsupportedResourceException
     */
    public function testReplaceFailsIfNoResource()
    {
        $collection = new ResourceCollection();

        $collection->replace(array(
            'foobar',
        ));
    }

    /**
     * @expectedException \OutOfBoundsException
     */
    public function testReplaceFailsIfNoSuchOffset()
    {
        $collection = new ResourceCollection();

        $collection->get(0);
    }

    public function testRemove()
    {
        $collection = new ResourceCollection(array(
            $dir1 = $this->getMock('Webmozart\Puli\Resource\DirectoryResourceInterface'),
            $dir2 = $this->getMock('Webmozart\Puli\Resource\DirectoryResourceInterface'),
            $file = $this->getMock('Webmozart\Puli\Resource\FileResourceInterface'),
        ));

        $collection->remove(1);

        $this->assertCount(2, $collection);
        $this->assertSame(array($dir1, 2 => $file), $collection->toArray());
        $this->assertSame($dir1, $collection->get(0));
        $this->assertSame($file, $collection->get(2));
    }

    public function testHas()
    {
        $collection = new ResourceCollection(array(
            $dir1 = $this->getMock('Webmozart\Puli\Resource\DirectoryResourceInterface'),
            $dir2 = $this->getMock('Webmozart\Puli\Resource\DirectoryResourceInterface'),
            $file = $this->getMock('Webmozart\Puli\Resource\FileResourceInterface'),
        ));

        $this->assertFalse($collection->has(-1));
        $this->assertTrue($collection->has(0));
        $this->assertTrue($collection->has(1));
        $this->assertTrue($collection->has(2));
        $this->assertFalse($collection->has(3));
    }

    public function testClear()
    {
        $collection = new ResourceCollection(array(
            $dir1 = $this->getMock('Webmozart\Puli\Resource\DirectoryResourceInterface'),
            $dir2 = $this->getMock('Webmozart\Puli\Resource\DirectoryResourceInterface'),
            $file = $this->getMock('Webmozart\Puli\Resource\FileResourceInterface'),
        ));

        $collection->clear();

        $this->assertCount(0, $collection);
    }

    public function testAdd()
    {
        $collection = new ResourceCollection(array(
            $dir = $this->getMock('Webmozart\Puli\Resource\DirectoryResourceInterface'),
        ));

        $collection->add($file = $this->getMock('Webmozart\Puli\Resource\FileResourceInterface'));

        $this->assertCount(2, $collection);
        $this->assertSame(array($dir, $file), $collection->toArray());
        $this->assertSame($dir, $collection->get(0));
        $this->assertSame($file, $collection->get(1));
    }

    public function testIsEmpty()
    {
        $collection = new ResourceCollection();

        $this->assertTrue($collection->isEmpty());

        $collection->add($this->getMock('Webmozart\Puli\Resource\FileResourceInterface'));

        $this->assertFalse($collection->isEmpty());

        $collection->remove(0);

        $this->assertTrue($collection->isEmpty());
    }

    public function testArrayAccess()
    {
        $collection = new ResourceCollection();
        $collection[] = $this->getMock('Webmozart\Puli\Resource\FileResourceInterface');

    }
}
