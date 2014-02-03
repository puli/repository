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

use Webmozart\Puli\Resource\DirectoryResource;
use Webmozart\Puli\Resource\FileResource;

/**
 * @since  %%NextVersion%%
 * @author Bernhard Schussek <bschussek@gmail.com>
 */
class DirectoryResourceTest extends \PHPUnit_Framework_TestCase
{
    public function testAdd()
    {
        $directory = new DirectoryResource('/webmozart/puli');
        $child1 = new FileResource('/webmozart/puli/child1');
        $child2 = new DirectoryResource('/webmozart/puli/child2');

        $directory->add($child1);
        $directory->add($child2);

        $this->assertEquals(array($child1, $child2), $directory->all());
    }

    /**
     * @expectedException \InvalidArgumentException
     */
    public function testAddFailsIfNotChild()
    {
        $directory = new DirectoryResource('/webmozart/puli');

        $directory->add(new FileResource('/webmozart/foo'));
    }

    /**
     * @expectedException \InvalidArgumentException
     */
    public function testAddFailsIfNotDirectChild()
    {
        $directory = new DirectoryResource('/webmozart/puli');

        $directory->add(new FileResource('/webmozart/puli/foo/bar'));
    }

    public function testAllSorts()
    {
        $directory = new DirectoryResource('/webmozart/puli');
        $child1 = new FileResource('/webmozart/puli/foo');
        $child2 = new FileResource('/webmozart/puli/bar');

        $directory->add($child1);
        $directory->add($child2);

        $this->assertEquals(array($child2, $child1), $directory->all());
    }

    public function testIterate()
    {
        $directory = new DirectoryResource('/webmozart/puli');
        $child1 = new FileResource('/webmozart/puli/foo');
        $child2 = new FileResource('/webmozart/puli/bar');

        $directory->add($child1);
        $directory->add($child2);

        $this->assertEquals(array($child2, $child1), iterator_to_array($directory));
    }

    public function testCount()
    {
        $directory = new DirectoryResource('/webmozart/puli');

        $this->assertCount(0, $directory);

        $directory->add(new FileResource('/webmozart/puli/foo'));

        $this->assertCount(1, $directory);

         $directory->add(new FileResource('/webmozart/puli/bar'));

        $this->assertCount(2, $directory);
    }

    public function testGet()
    {
        $directory = new DirectoryResource('/webmozart/puli');
        $child1 = new FileResource('/webmozart/puli/child1');
        $child2 = new DirectoryResource('/webmozart/puli/child2');

        $directory->add($child1);
        $directory->add($child2);

        $this->assertEquals($child1, $directory->get('child1'));
        $this->assertEquals($child2, $directory->get('child2'));
    }

    /**
     * @expectedException \OutOfBoundsException
     */
    public function testGetExpectsValidFile()
    {
        $directory = new DirectoryResource('/webmozart/puli');

        $directory->get('foo');
    }

    public function testContains()
    {
        $directory = new DirectoryResource('/webmozart/puli');

        $this->assertFalse($directory->contains('child'));

        $directory->add(new FileResource('/webmozart/puli/child'));

        $this->assertTrue($directory->contains('child'));
    }

    public function testRemove()
    {
        $directory = new DirectoryResource('/webmozart/puli');
        $directory->add(new FileResource('/webmozart/puli/child'));

        $this->assertTrue($directory->contains('child'));

        $directory->remove('child');

        $this->assertFalse($directory->contains('child'));
    }

    /**
     * @expectedException \OutOfBoundsException
     */
    public function testRemoveExpectsValidFile()
    {
        $directory = new DirectoryResource('/webmozart/puli');

        $directory->remove('foo');
    }

    public function testArrayAccess()
    {
        $directory = new DirectoryResource('/webmozart/puli');
        $child = new FileResource('/webmozart/puli/child');

        $directory[] = $child;

        $this->assertEquals($child, $directory['child']);
        $this->assertTrue(isset($directory['child']));

        unset($directory['child']);

        $this->assertFalse(isset($directory['child']));
    }
}
