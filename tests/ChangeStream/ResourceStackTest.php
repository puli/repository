<?php

/*
 * This file is part of the puli/repository package.
 *
 * (c) Bernhard Schussek <bschussek@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Puli\Repository\Tests\ChangeStream;

use Puli\Repository\ChangeStream\ResourceStack;
use Puli\Repository\Tests\Resource\Collection\ArrayResourceCollectionTest;
use PHPUnit_Framework_TestCase;

/**
 * @author Titouan Galopin <galopintitouan@gmail.com>
 */
class ResourceStackTest extends PHPUnit_Framework_TestCase
{
    public function testGetCurrent()
    {
        $stack = new ResourceStack(array(
            $v1 = $this->getMock('Puli\Repository\Api\Resource\PuliResource'),
            $v2 = $this->getMock('Puli\Repository\Api\Resource\PuliResource'),
            $v3 = $this->getMock('Puli\Repository\Api\Resource\PuliResource'),
            $v4 = $this->getMock('Puli\Repository\Api\Resource\PuliResource'),
        ));

        $this->assertSame($v4, $stack->getCurrent());
    }

    /**
     * @expectedException \RuntimeException
     */
    public function testGetCurrentFails()
    {
        $stack = new ResourceStack(array());
        $stack->getCurrent();
    }

    public function testGetCurrentVersion()
    {
        $stack = new ResourceStack(array(
            $v1 = $this->getMock('Puli\Repository\Api\Resource\PuliResource'),
            $v2 = $this->getMock('Puli\Repository\Api\Resource\PuliResource'),
            $v3 = $this->getMock('Puli\Repository\Api\Resource\PuliResource'),
            $v4 = $this->getMock('Puli\Repository\Api\Resource\PuliResource'),
        ));

        $this->assertSame(3, $stack->getCurrentVersion());
    }

    /**
     * @expectedException \RuntimeException
     */
    public function testGetCurrentVersionFails()
    {
        $stack = new ResourceStack(array());
        $stack->getCurrentVersion();
    }

    public function testGetFirst()
    {
        $stack = new ResourceStack(array(
            $v1 = $this->getMock('Puli\Repository\Api\Resource\PuliResource'),
            $v2 = $this->getMock('Puli\Repository\Api\Resource\PuliResource'),
            $v3 = $this->getMock('Puli\Repository\Api\Resource\PuliResource'),
            $v4 = $this->getMock('Puli\Repository\Api\Resource\PuliResource'),
        ));

        $this->assertSame($v1, $stack->getFirst());
    }

    /**
     * @expectedException \RuntimeException
     */
    public function testGetFirstFails()
    {
        $stack = new ResourceStack(array());
        $stack->getFirst();
    }

    public function testGetFirstVersion()
    {
        $stack = new ResourceStack(array(
            $v1 = $this->getMock('Puli\Repository\Api\Resource\PuliResource'),
            $v2 = $this->getMock('Puli\Repository\Api\Resource\PuliResource'),
            $v3 = $this->getMock('Puli\Repository\Api\Resource\PuliResource'),
            $v4 = $this->getMock('Puli\Repository\Api\Resource\PuliResource'),
        ));

        $this->assertSame(0, $stack->getFirstVersion());
    }

    /**
     * @expectedException \RuntimeException
     */
    public function testGetFirstVersionFails()
    {
        $stack = new ResourceStack(array());
        $stack->getCurrentVersion();
    }

    public function testGet()
    {
        $stack = new ResourceStack(array(
            $v1 = $this->getMock('Puli\Repository\Api\Resource\PuliResource'),
            $v2 = $this->getMock('Puli\Repository\Api\Resource\PuliResource'),
            $v3 = $this->getMock('Puli\Repository\Api\Resource\PuliResource'),
            $v4 = $this->getMock('Puli\Repository\Api\Resource\PuliResource'),
        ));

        $this->assertSame($v1, $stack->get(0));
        $this->assertSame($v2, $stack->get(1));
        $this->assertSame($v3, $stack->get(2));
        $this->assertSame($v4, $stack->get(3));
    }

    /**
     * @expectedException \RuntimeException
     */
    public function testGetFailsEmpty()
    {
        $stack = new ResourceStack(array());
        $stack->get(0);
    }

    /**
     * @expectedException \InvalidArgumentException
     */
    public function testGetFailsInvalid()
    {
        $stack = new ResourceStack(array($this->getMock('Puli\Repository\Api\Resource\PuliResource')));
        $stack->get(2);
    }

    public function testGetVersions()
    {
        $stack = new ResourceStack(array(
            $v1 = $this->getMock('Puli\Repository\Api\Resource\PuliResource'),
            $v2 = $this->getMock('Puli\Repository\Api\Resource\PuliResource'),
            $v3 = $this->getMock('Puli\Repository\Api\Resource\PuliResource'),
            $v4 = $this->getMock('Puli\Repository\Api\Resource\PuliResource'),
        ));

        $this->assertSame(array(0, 1, 2, 3), $stack->getVersions());
    }
}
