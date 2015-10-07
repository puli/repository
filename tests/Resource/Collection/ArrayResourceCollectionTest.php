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
use Puli\Repository\Resource\Collection\ArrayResourceCollection;

/**
 * @author Bernhard Schussek <bschussek@gmail.com>
 */
class ArrayResourceCollectionTest extends PHPUnit_Framework_TestCase
{
    public function testConstruct()
    {
        $collection = new ArrayResourceCollection(array(
            $dir = $this->getMock('Puli\Repository\Api\Resource\PuliResource'),
            $file = $this->getMock('Puli\Repository\Api\Resource\BodyResource'),
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
        new ArrayResourceCollection('foobar');
    }

    /**
     * @expectedException \InvalidArgumentException
     */
    public function testConstructFailsIfNoResource()
    {
        new ArrayResourceCollection(array(
            'foobar',
        ));
    }

    public function testReplace()
    {
        $collection = new ArrayResourceCollection(array(
            $this->getMock('Puli\Repository\Api\Resource\PuliResource'),
            $this->getMock('Puli\Repository\Api\Resource\PuliResource'),
        ));

        $collection->replace(array(
            2 => $dir = $this->getMock('Puli\Repository\Api\Resource\PuliResource'),
            3 => $file = $this->getMock('Puli\Repository\Api\Resource\BodyResource'),
        ));

        $this->assertCount(2, $collection);
        $this->assertSame(array(2 => $dir, 3 => $file), $collection->toArray());
        $this->assertSame($dir, $collection->get(2));
        $this->assertSame($file, $collection->get(3));
    }

    /**
     * @expectedException \InvalidArgumentException
     */
    public function testReplaceFailsIfNoTraversable()
    {
        $collection = new ArrayResourceCollection();

        $collection->replace('foobar');
    }

    /**
     * @expectedException \InvalidArgumentException
     */
    public function testReplaceFailsIfNoResource()
    {
        $collection = new ArrayResourceCollection();

        $collection->replace(array(
            'foobar',
        ));
    }

    public function testMerge()
    {
        $collection = new ArrayResourceCollection(array(
            2 => $dir1 = $this->getMock('Puli\Repository\Api\Resource\PuliResource'),
            3 => $dir2 = $this->getMock('Puli\Repository\Api\Resource\PuliResource'),
        ));

        $collection->merge(array(
            $dir3 = $this->getMock('Puli\Repository\Api\Resource\PuliResource'),
            $file = $this->getMock('Puli\Repository\Api\Resource\BodyResource'),
        ));

        $this->assertCount(4, $collection);
        $this->assertSame(array(2 => $dir1, 3 => $dir2, 4 => $dir3, 5 => $file), $collection->toArray());
        $this->assertSame($dir1, $collection->get(2));
        $this->assertSame($dir2, $collection->get(3));
        $this->assertSame($dir3, $collection->get(4));
        $this->assertSame($file, $collection->get(5));
    }

    /**
     * @expectedException \InvalidArgumentException
     */
    public function testMergeFailsIfNoTraversable()
    {
        $collection = new ArrayResourceCollection();

        $collection->merge('foobar');
    }

    /**
     * @expectedException \InvalidArgumentException
     */
    public function testMergeFailsIfNoResource()
    {
        $collection = new ArrayResourceCollection();

        $collection->merge(array(
            'foobar',
        ));
    }

    /**
     * @expectedException \OutOfBoundsException
     */
    public function testGetFailsIfNoSuchOffset()
    {
        $collection = new ArrayResourceCollection();

        $collection->get(0);
    }

    public function testSet()
    {
        $collection = new ArrayResourceCollection(array(
            1 => $this->getMock('Puli\Repository\Api\Resource\PuliResource'),
            2 => $file1 = $this->getMock('Puli\Repository\Api\Resource\BodyResource'),
        ));

        $this->assertCount(2, $collection);
        $this->assertSame($file1, $collection->get(2));

        $collection->set(2, $file2 = $this->getMock('Puli\Repository\Api\Resource\BodyResource'));

        $this->assertCount(2, $collection);
        $this->assertSame($file2, $collection->get(2));
    }

    public function testRemove()
    {
        $collection = new ArrayResourceCollection(array(
            $dir1 = $this->getMock('Puli\Repository\Api\Resource\PuliResource'),
            $dir2 = $this->getMock('Puli\Repository\Api\Resource\PuliResource'),
            $file = $this->getMock('Puli\Repository\Api\Resource\BodyResource'),
        ));

        $collection->remove(1);

        $this->assertCount(2, $collection);
        $this->assertSame(array($dir1, 2 => $file), $collection->toArray());
        $this->assertSame($dir1, $collection->get(0));
        $this->assertSame($file, $collection->get(2));
    }

    public function testHas()
    {
        $collection = new ArrayResourceCollection(array(
            $dir1 = $this->getMock('Puli\Repository\Api\Resource\PuliResource'),
            $dir2 = $this->getMock('Puli\Repository\Api\Resource\PuliResource'),
            $file = $this->getMock('Puli\Repository\Api\Resource\BodyResource'),
        ));

        $this->assertFalse($collection->has(-1));
        $this->assertTrue($collection->has(0));
        $this->assertTrue($collection->has(1));
        $this->assertTrue($collection->has(2));
        $this->assertFalse($collection->has(3));
    }

    public function testClear()
    {
        $collection = new ArrayResourceCollection(array(
            $dir1 = $this->getMock('Puli\Repository\Api\Resource\PuliResource'),
            $dir2 = $this->getMock('Puli\Repository\Api\Resource\PuliResource'),
            $file = $this->getMock('Puli\Repository\Api\Resource\BodyResource'),
        ));

        $collection->clear();

        $this->assertCount(0, $collection);
    }

    public function testAdd()
    {
        $collection = new ArrayResourceCollection(array(
            $dir = $this->getMock('Puli\Repository\Api\Resource\PuliResource'),
        ));

        $collection->add($file = $this->getMock('Puli\Repository\Api\Resource\BodyResource'));

        $this->assertCount(2, $collection);
        $this->assertSame(array($dir, $file), $collection->toArray());
        $this->assertSame($dir, $collection->get(0));
        $this->assertSame($file, $collection->get(1));
    }

    public function testIsEmpty()
    {
        $collection = new ArrayResourceCollection();

        $this->assertTrue($collection->isEmpty());

        $collection->add($this->getMock('Puli\Repository\Api\Resource\BodyResource'));

        $this->assertFalse($collection->isEmpty());

        $collection->remove(0);

        $this->assertTrue($collection->isEmpty());
    }

    public function testArrayAccess()
    {
        $collection = new ArrayResourceCollection();
        $collection[] = $this->getMock('Puli\Repository\Api\Resource\BodyResource');
    }
}
