<?php

/*
 * This file is part of the Puli package.
 *
 * (c) Bernhard Schussek <bschussek@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Webmozart\Puli\Tests\Extension\Symfony\HttpKernel;

use Webmozart\Puli\Extension\Symfony\HttpKernel\ChainableKernelFileLocator;

/**
 * @since  1.0
 * @author Bernhard Schussek <bschussek@gmail.com>
 */
class ChainableKernelFileLocatorTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var \PHPUnit_Framework_MockObject_MockObject
     */
    private $kernel;

    /**
     * @var ChainableKernelFileLocator
     */
    private $locator;

    protected function setUp()
    {
        $this->kernel = $this->getMock('Symfony\Component\HttpKernel\KernelInterface');
        $this->locator = new ChainableKernelFileLocator($this->kernel);
    }

    public function testSupportsOnlyPathsStartingWithAt()
    {
        $this->assertTrue($this->locator->supports('@path'));
        $this->assertFalse($this->locator->supports('path'));
        $this->assertFalse($this->locator->supports('/path'));
        $this->assertFalse($this->locator->supports('./path'));
    }
}
