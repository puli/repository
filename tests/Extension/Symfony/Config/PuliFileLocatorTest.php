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

use Puli\Extension\Symfony\Config\PuliFileLocator;
use Puli\Repository\ResourceRepository;
use Puli\Tests\Resource\TestFile;

/**
 * @since  1.0
 * @author Bernhard Schussek <bschussek@gmail.com>
 */
class PuliFileLocatorTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var ResourceRepository
     */
    private $repo;

    /**
     * @var PuliFileLocator
     */
    private $locator;

    protected function setUp()
    {
        $this->repo = new ResourceRepository();
        $this->locator = new PuliFileLocator($this->repo);
    }

    public function testSupportOnlyPathsStartingWithSlash()
    {
        $this->assertTrue($this->locator->supports('/foo/bar'));
        $this->assertFalse($this->locator->supports('foo/bar'));
        $this->assertFalse($this->locator->supports('../foo/bar'));
        $this->assertFalse($this->locator->supports('@foo/bar'));
    }

    public function testAcceptAbsolutePaths()
    {
        $path = __DIR__.'/Fixtures/main/routing.yml';

        $this->assertSame($path, $this->locator->locate($path));
    }

    public function testAcceptKnownPaths()
    {
        $path = __DIR__.'/Fixtures/main/routing.yml';

        $this->repo->add('/webmozart/puli', __DIR__.'/Fixtures/main');

        $this->assertSame($path, $this->locator->locate('/webmozart/puli/routing.yml'));
    }

    /**
     * @expectedException \RuntimeException
     */
    public function testAcceptRelativePaths()
    {
        // Unfortunately we receive the absolute path, not the Puli path, in
        // the second argument. For this reason, files referenced via a
        // relative path cannot be overridden.

        // The locator throws an exception in order to prevent relative paths.
        $this->locator->locate('routing.yml', __DIR__.'/Fixtures/main');
    }

    public function testReturnAllPathsIfFirstIsFalse()
    {
        $mainPath = __DIR__.'/Fixtures/main/routing.yml';
        $overriddenPath = __DIR__.'/Fixtures/override/routing.yml';

        $this->repo->add('/webmozart/puli', __DIR__.'/Fixtures/main');
        $this->repo->add('/webmozart/puli', __DIR__.'/Fixtures/override');

        $order = array(
            $overriddenPath,
            $mainPath
        );

        $this->assertSame($order, $this->locator->locate('/webmozart/puli/routing.yml', null, false));
    }

    /**
     * @expectedException \InvalidArgumentException
     */
    public function testRejectUnknownPaths()
    {
        $this->locator->locate('/foo/bar');
    }

    /**
     * @expectedException \InvalidArgumentException
     */
    public function testRejectNonLocalPaths()
    {
        $this->repo->add('/webmozart/puli', new TestFile());

        $this->locator->locate('/webmozart/puli');
    }
}
