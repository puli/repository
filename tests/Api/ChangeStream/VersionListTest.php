<?php

/*
 * This file is part of the puli/repository package.
 *
 * (c) Bernhard Schussek <bschussek@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Puli\Repository\Tests\Api\ChangeStream;

use PHPUnit_Framework_MockObject_MockObject;
use PHPUnit_Framework_TestCase;
use Puli\Repository\Api\ChangeStream\VersionList;
use Puli\Repository\Api\Resource\PuliResource;

/**
 * @author Titouan Galopin <galopintitouan@gmail.com>
 * @author Bernhard Schussek <bschussek@gmail.com>
 */
class VersionListTest extends PHPUnit_Framework_TestCase
{
    /**
     * @expectedException \InvalidArgumentException
     */
    public function testFailIfEmptyPath()
    {
        new VersionList('', array($this->getMockResource()));
    }

    /**
     * @expectedException \InvalidArgumentException
     */
    public function testFailIfInvalidPath()
    {
        new VersionList(1234, array($this->getMockResource()));
    }

    /**
     * @expectedException \InvalidArgumentException
     */
    public function testFailIfNoVersion()
    {
        new VersionList('/path', array());
    }

    /**
     * @expectedException \InvalidArgumentException
     */
    public function testFailIfInvalidVersions()
    {
        new VersionList('/path', array(new \stdClass()));
    }

    public function testGetCurrentVersion()
    {
        $list = new VersionList('/path', array(
            $this->getMockResource(),
            $this->getMockResource(),
            $this->getMockResource(),
            $this->getMockResource(),
        ));

        $this->assertSame(3, $list->getCurrentVersion());
    }

    public function testGetCurrent()
    {
        $list = new VersionList('/path', array(
            $this->getMockResource(),
            $this->getMockResource(),
            $this->getMockResource(),
            $v4 = $this->getMockResource(),
        ));

        $this->assertSame($v4, $list->getCurrent());
    }

    public function testGetFirstVersion()
    {
        $list = new VersionList('/path', array(
            $this->getMockResource(),
            $this->getMockResource(),
            $this->getMockResource(),
            $this->getMockResource(),
        ));

        $this->assertSame(0, $list->getFirstVersion());
    }

    public function testGetFirst()
    {
        $list = new VersionList('/path', array(
            $v1 = $this->getMockResource(),
            $this->getMockResource(),
            $this->getMockResource(),
            $this->getMockResource(),
        ));

        $this->assertSame($v1, $list->getFirst());
    }

    public function testGet()
    {
        $list = new VersionList('/path', array(
            $v1 = $this->getMockResource(),
            $v2 = $this->getMockResource(),
            $v3 = $this->getMockResource(),
            $v4 = $this->getMockResource(),
        ));

        $this->assertSame($v1, $list->get(0));
        $this->assertSame($v2, $list->get(1));
        $this->assertSame($v3, $list->get(2));
        $this->assertSame($v4, $list->get(3));
    }

    /**
     * @expectedException \OutOfBoundsException
     */
    public function testGetFailsIfNotFound()
    {
        $list = new VersionList('/path', array($this->getMockResource()));

        $list->get(2);
    }

    public function testGetVersions()
    {
        $list = new VersionList('/path', array(
            $this->getMockResource(),
            $this->getMockResource(),
            $this->getMockResource(),
            $this->getMockResource(),
        ));

        $this->assertSame(array(0, 1, 2, 3), $list->getVersions());
    }

    public function testCount()
    {
        $list = new VersionList('/path', array(
            $this->getMockResource(),
            $this->getMockResource(),
            $this->getMockResource(),
            $this->getMockResource(),
        ));

        $this->assertCount(4, $list);
    }

    public function testIterate()
    {
        $list = new VersionList('/path', array(
            $v1 = $this->getMockResource(),
            $v2 = $this->getMockResource(),
            $v3 = $this->getMockResource(),
            $v4 = $this->getMockResource(),
        ));

        $this->assertSame(array($v1, $v2, $v3, $v4), iterator_to_array($list));
    }

    public function testToArray()
    {
        $list = new VersionList('/path', array(
            $v1 = $this->getMockResource(),
            $v2 = $this->getMockResource(),
            $v3 = $this->getMockResource(),
            $v4 = $this->getMockResource(),
        ));

        $this->assertSame(array($v1, $v2, $v3, $v4), $list->toArray());
    }

    public function testArrayAccess()
    {
        $list = new VersionList('/path', array(
            $v1 = $this->getMockResource(),
            $v2 = $this->getMockResource(),
            $v3 = $this->getMockResource(),
            $v4 = $this->getMockResource(),
        ));

        $this->assertSame($v1, $list[0]);
        $this->assertSame($v2, $list[1]);
        $this->assertSame($v3, $list[2]);
        $this->assertSame($v4, $list[3]);
        $this->assertTrue(isset($list[0]));
        $this->assertFalse(isset($list[4]));
    }

    /**
     * @return PHPUnit_Framework_MockObject_MockObject|PuliResource
     */
    private function getMockResource()
    {
        return $this->getMock('Puli\Repository\Api\Resource\PuliResource');
    }
}
