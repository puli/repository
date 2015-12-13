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

/**
 * @author Titouan Galopin <galopintitouan@gmail.com>
 */
class ResourceStackTest extends ArrayResourceCollectionTest
{
    public function testGetCurrentVersion()
    {
        $stack = new ResourceStack(array(
            $v1 = $this->getMock('Puli\Repository\Api\Resource\PuliResource'),
            $v2 = $this->getMock('Puli\Repository\Api\Resource\PuliResource'),
            $v3 = $this->getMock('Puli\Repository\Api\Resource\PuliResource'),
            $v4 = $this->getMock('Puli\Repository\Api\Resource\PuliResource'),
        ));

        $this->assertSame($v4, $stack->getCurrentVersion());
    }

    /**
     * @expectedException \RuntimeException
     */
    public function testGetCurrentVersionFails()
    {
        $stack = new ResourceStack(array());
        $stack->getCurrentVersion();
    }

    public function testGetFirstVersion()
    {
        $stack = new ResourceStack(array(
            $v1 = $this->getMock('Puli\Repository\Api\Resource\PuliResource'),
            $v2 = $this->getMock('Puli\Repository\Api\Resource\PuliResource'),
            $v3 = $this->getMock('Puli\Repository\Api\Resource\PuliResource'),
            $v4 = $this->getMock('Puli\Repository\Api\Resource\PuliResource'),
        ));

        $this->assertSame($v1, $stack->getFirstVersion());
    }

    /**
     * @expectedException \RuntimeException
     */
    public function testGetFirstVersionFails()
    {
        $stack = new ResourceStack(array());
        $stack->getCurrentVersion();
    }

    public function testGetVersion()
    {
        $stack = new ResourceStack(array(
            $v1 = $this->getMock('Puli\Repository\Api\Resource\PuliResource'),
            $v2 = $this->getMock('Puli\Repository\Api\Resource\PuliResource'),
            $v3 = $this->getMock('Puli\Repository\Api\Resource\PuliResource'),
            $v4 = $this->getMock('Puli\Repository\Api\Resource\PuliResource'),
        ));

        $this->assertSame($v1, $stack->getVersion(0));
        $this->assertSame($v2, $stack->getVersion(1));
        $this->assertSame($v3, $stack->getVersion(2));
        $this->assertSame($v4, $stack->getVersion(3));
    }

    /**
     * @expectedException \RuntimeException
     */
    public function testGetVersionFailsEmpty()
    {
        $stack = new ResourceStack(array());
        $stack->getVersion(0);
    }

    /**
     * @expectedException \InvalidArgumentException
     */
    public function testGetVersionFailsInvalid()
    {
        $stack = new ResourceStack(array($this->getMock('Puli\Repository\Api\Resource\PuliResource')));
        $stack->getVersion(2);
    }

    public function testGetAvailableVersion()
    {
        $stack = new ResourceStack(array(
            $v1 = $this->getMock('Puli\Repository\Api\Resource\PuliResource'),
            $v2 = $this->getMock('Puli\Repository\Api\Resource\PuliResource'),
            $v3 = $this->getMock('Puli\Repository\Api\Resource\PuliResource'),
            $v4 = $this->getMock('Puli\Repository\Api\Resource\PuliResource'),
        ));

        $this->assertSame(array(0, 1, 2, 3), $stack->getAvailableVersions());
    }
}
