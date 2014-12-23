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

use PHPUnit_Framework_TestCase;
use Puli\Repository\Resource\LocalDirectoryResource;
use Puli\Repository\Resource\LocalFileResource;
use Puli\Repository\Resource\LocalResourceCollection;

/**
 * @since  1.0
 * @author Bernhard Schussek <bschussek@gmail.com>
 */
class LocalResourceCollectionTest extends PHPUnit_Framework_TestCase
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

    public function testGetLocalPaths()
    {
        $collection = new LocalResourceCollection(array(
            $dir = new LocalDirectoryResource($this->fixturesDir.'/dir1'),
            $file = new LocalFileResource($this->fixturesDir.'/file3'),
        ));

        $this->assertSame(array(
            $dir->getLocalPath(),
            $file->getLocalPath(),
        ), $collection->getLocalPaths());
    }

    public function testGetLocalPathsIgnoresNonLocalResources()
    {
        $collection = new LocalResourceCollection(array(
            $dir = new LocalDirectoryResource($this->fixturesDir.'/dir1'),
            $file = new LocalFileResource($this->fixturesDir.'/file3'),
            new TestFile(),
            new TestDirectory(),
        ));

        $this->assertSame(array(
            $dir->getLocalPath(),
            $file->getLocalPath(),
        ), $collection->getLocalPaths());
    }
}
