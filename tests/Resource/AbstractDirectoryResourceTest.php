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

use Webmozart\Puli\Resource\DirectoryLoaderInterface;
use Webmozart\Puli\Resource\DirectoryResourceInterface;
use Webmozart\Puli\Resource\FileResourceInterface;

/**
 * @since  1.0
 * @author Bernhard Schussek <bschussek@gmail.com>
 */
abstract class AbstractDirectoryResourceTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @param string                   $path
     * @param DirectoryLoaderInterface $loader
     *
     * @return DirectoryResourceInterface
     */
    abstract protected function createDir($path, DirectoryLoaderInterface $loader = null);

    /**
     * @param string $path
     *
     * @return FileResourceInterface
     */
    abstract protected function createFile($path);

    public function testAdd()
    {
        $directory = $this->createDir('/webmozart/puli');
        $directory->add($child1 = $this->createFile('/webmozart/puli/child1'));
        $directory->add($child2 = $this->createDir('/webmozart/puli/child2'));

        $entries = $directory->listEntries();

        $this->assertInstanceOf('Webmozart\Puli\Resource\ResourceCollectionInterface', $entries);
        $this->assertCount(2, $entries);
        $this->assertEquals(array('child1' => $child1, 'child2' => $child2), $entries->toArray());
    }

    /**
     * @expectedException \InvalidArgumentException
     */
    public function testAddFailsIfNotChild()
    {
        $directory = $this->createDir('/webmozart/puli');

        $directory->add($this->createFile('/webmozart/foo'));
    }

    /**
     * @expectedException \InvalidArgumentException
     */
    public function testAddFailsIfNotDirectChild()
    {
        $directory = $this->createDir('/webmozart/puli');

        $directory->add($this->createFile('/webmozart/puli/foo/bar'));
    }

    public function testListEntriesSorts()
    {
        $directory = $this->createDir('/webmozart/puli');
        $directory->add($child1 = $this->createFile('/webmozart/puli/foo'));
        $directory->add($child2 = $this->createFile('/webmozart/puli/bar'));

        $entries = $directory->listEntries();

        $this->assertInstanceOf('Webmozart\Puli\Resource\ResourceCollectionInterface', $entries);
        $this->assertCount(2, $entries);
        $this->assertEquals(array('bar' => $child2, 'foo' => $child1), $entries->toArray());
    }

    public function testListEntriesFromLoader()
    {
        $loadedEntries = array(
            // Loaded resources are NOT sorted!
            // They are expected to be returned sorted by the loader
            $this->createFile('/webmozart/puli/foo'),
            $this->createFile('/webmozart/puli/bar'),
        );

        $loader = $this->getMock('Webmozart\Puli\Resource\DirectoryLoaderInterface');

        $loader->expects($this->once())
            ->method('loadDirectoryEntries')
            ->with($this->isInstanceOf('Webmozart\Puli\Resource\DirectoryResourceInterface'))
            ->will($this->returnValue($loadedEntries));

        $directory = $this->createDir('/path', $loader);
        $entries = $directory->listEntries();

        $this->assertEquals(array('foo' => $loadedEntries[0], 'bar' => $loadedEntries[1]), $entries->toArray());
    }

    /**
     * @expectedException \Webmozart\Puli\Locator\ResourceNotFoundException
     */
    public function testGetDot()
    {
        $directory = $this->createDir('/path');

        $directory->get('.');
    }

    /**
     * @expectedException \Webmozart\Puli\Locator\ResourceNotFoundException
     */
    public function testGetDotDot()
    {
        $directory = $this->createDir('/path');

        $directory->get('..');
    }

    public function testGet()
    {
        $directory = $this->createDir('/webmozart/puli');
        $directory->add($child1 = $this->createFile('/webmozart/puli/child1'));
        $directory->add($child2 = $this->createDir('/webmozart/puli/child2'));

        $this->assertEquals($child1, $directory->get('child1'));
        $this->assertEquals($child2, $directory->get('child2'));
    }

    /**
     * @expectedException \Webmozart\Puli\Locator\ResourceNotFoundException
     */
    public function testGetExpectsValidFile()
    {
        $directory = $this->createDir('/path');

        $directory->get('foo');
    }

    public function testContains()
    {
        $directory = $this->createDir('/webmozart/puli');

        $this->assertFalse($directory->contains('child'));

        $directory->add($this->createFile('/webmozart/puli/child'));

        $this->assertTrue($directory->contains('child'));
    }

    public function testRemove()
    {
        $directory = $this->createDir('/webmozart/puli');
        $directory->add($this->createFile('/webmozart/puli/child'));

        $this->assertTrue($directory->contains('child'));

        $directory->remove('child');

        $this->assertFalse($directory->contains('child'));
    }

    /**
     * @expectedException \Webmozart\Puli\Locator\ResourceNotFoundException
     */
    public function testRemoveExpectsValidFile()
    {
        $directory = $this->createDir('/path');

        $directory->remove('foo');
    }

    public function testCopy()
    {
        $directory = $this->createDir('/old/path');

        $copy = $directory->copyTo('/new/path');

        $this->assertNotSame($copy, $directory);
        $this->assertSame('/new/path', $copy->getPath());
        $this->assertCount(0, $copy->listEntries());
    }

    public function testCopyWithChildren()
    {
        $directory = $this->createDir('/webmozart/puli');
        $directory->add($child1 = $this->createFile('/webmozart/puli/child1'));
        $directory->add($child2 = $this->createDir('/webmozart/puli/child2'));

        $copy = $directory->copyTo('/new/path');

        $this->assertNotSame($copy, $directory);
        $this->assertSame('/new/path', $copy->getPath());
        $this->assertCount(2, $copy->listEntries());
        $this->assertEquals($child1->copyTo('/new/path/child1'), $copy->get('child1'));
        $this->assertEquals($child2->copyTo('/new/path/child2'), $copy->get('child2'));
    }

    public function testCopyToRoot()
    {
        $directory = $this->createDir('/webmozart/puli');
        $directory->add($child1 = $this->createFile('/webmozart/puli/child1'));
        $directory->add($child2 = $this->createDir('/webmozart/puli/child2'));

        $copy = $directory->copyTo('/');

        $this->assertNotSame($copy, $directory);
        $this->assertSame('/', $copy->getPath());
        $this->assertCount(2, $copy->listEntries());
        $this->assertEquals($child1->copyTo('/child1'), $copy->get('child1'));
        $this->assertEquals($child2->copyTo('/child2'), $copy->get('child2'));
    }

    public function testCopyDoesNotLoadEntries()
    {
        $loader = $this->getMock('Webmozart\Puli\Resource\DirectoryLoaderInterface');

        $loader->expects($this->never())
            ->method('loadDirectoryEntries');

        $directory = $this->createDir($loader);

        $copy = $directory->copyTo('/new/path');

        $this->assertNotSame($copy, $directory);
        $this->assertSame('/new/path', $copy->getPath());
    }

    public function testOverride()
    {
        $directory = $this->createDir('/webmozart/puli');
        $directory->add($child1 = $this->createFile('/webmozart/puli/child1'));
        $directory->add($child2 = $this->createDir('/webmozart/puli/child2'));

        $overridden = $this->createDir('/other/path');
        $overridden->add($overriddenChild = $this->createFile('/other/path/child1'));

        $override = $directory->override($overridden);

        $this->assertNotSame($override, $directory);
        $this->assertNotSame($override, $overridden);
        $this->assertSame('/other/path', $override->getPath());
        $this->assertCount(2, $override->listEntries());
        $this->assertEquals($child1->override($overriddenChild), $override->get('child1'));
        $this->assertEquals($child2->copyTo('/other/path/child2'), $override->get('child2'));
    }

    public function testOverrideKeepsEntriesSorted()
    {
        $directory = $this->createDir('/webmozart/puli');
        $directory->add($child1 = $this->createFile('/webmozart/puli/foo'));
        $directory->add($child2 = $this->createDir('/webmozart/puli/bar'));

        $overridden = $this->createDir('/other/path');
        $overridden->add($overriddenChild1 = $this->createFile('/other/path/cdef'));
        $overridden->add($overriddenChild2 = $this->createFile('/other/path/foo'));

        $override = $directory->override($overridden);

        $this->assertSame('/other/path', $override->getPath());

        $entries = $override->listEntries();

        $this->assertCount(3, $entries);
        $this->assertEquals($child2->copyTo('/other/path/bar'), $entries['bar']);
        $this->assertEquals($overriddenChild1, $entries['cdef']);
        $this->assertEquals($child1->override($overriddenChild2), $entries['foo']);
    }

    public function testOverrideRoot()
    {
        $directory = $this->createDir('/webmozart/puli');
        $directory->add($child1 = $this->createFile('/webmozart/puli/child1'));
        $directory->add($child2 = $this->createDir('/webmozart/puli/child2'));

        $overridden = $this->createDir('/');
        $overridden->add($overriddenChild = $this->createFile('/child1'));

        $override = $directory->override($overridden);

        $this->assertNotSame($override, $directory);
        $this->assertNotSame($override, $overridden);
        $this->assertSame('/', $override->getPath());
        $this->assertCount(2, $override->listEntries());
        $this->assertEquals($child1->override($overriddenChild), $override->get('child1'));
        $this->assertEquals($child2->copyTo('/child2'), $override->get('child2'));
    }
}
