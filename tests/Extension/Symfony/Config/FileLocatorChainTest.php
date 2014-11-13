<?php

/*
 * This file is part of the Puli package.
 *
 * (c) Bernhard Schussek <bschussek@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Puli\Tests\Extension\Symfony\Config;

use Puli\Extension\Symfony\Config\FileLocatorChain;

/**
 * @since  1.0
 * @author Bernhard Schussek <bschussek@gmail.com>
 */
class FileLocatorChainTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var FileLocatorChain
     */
    private $chain;

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject
     */
    private $locator1;

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject
     */
    private $locator2;

    protected function setUp()
    {
        $this->locator1 = $this->getMock('Puli\Extension\Symfony\Config\ChainableFileLocatorInterface');
        $this->locator2 = $this->getMock('Puli\Extension\Symfony\Config\ChainableFileLocatorInterface');
        $this->chain = new FileLocatorChain(array($this->locator1));
        $this->chain->addLocator($this->locator2);
    }

    public function testFirstLocatorSupportsPath()
    {
        $this->locator1->expects($this->once())
            ->method('supports')
            ->with('PATH')
            ->will($this->returnValue(true));

        $this->locator1->expects($this->once())
            ->method('locate')
            ->with('PATH')
            ->will($this->returnValue('RESULT'));

        $this->locator2->expects($this->never())
            ->method('supports');

        $this->locator2->expects($this->never())
            ->method('locate');

        $this->assertSame('RESULT', $this->chain->locate('PATH'));
    }

    public function testSecondLocatorSupportsPath()
    {
        $this->locator1->expects($this->once())
            ->method('supports')
            ->with('PATH')
            ->will($this->returnValue(false));

        $this->locator1->expects($this->never())
            ->method('locate');

        $this->locator2->expects($this->once())
            ->method('supports')
            ->with('PATH')
            ->will($this->returnValue(true));

        $this->locator2->expects($this->once())
            ->method('locate')
            ->with('PATH')
            ->will($this->returnValue('RESULT'));

        $this->assertSame('RESULT', $this->chain->locate('PATH'));
    }

    /**
     * @expectedException \InvalidArgumentException
     */
    public function testNoLocatorSupportsPath()
    {
        $this->locator1->expects($this->once())
            ->method('supports')
            ->with('PATH')
            ->will($this->returnValue(false));

        $this->locator1->expects($this->never())
            ->method('locate');

        $this->locator2->expects($this->once())
            ->method('supports')
            ->with('PATH')
            ->will($this->returnValue(false));

        $this->locator2->expects($this->never())
            ->method('locate');

        $this->chain->locate('PATH');
    }
}
