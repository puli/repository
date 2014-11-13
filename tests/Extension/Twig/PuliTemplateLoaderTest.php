<?php

/*
 * This file is part of the Puli package.
 *
 * (c) Bernhard Schussek <bschussek@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Puli\Tests\Extension\Twig;

use Puli\Extension\Twig\PuliTemplateLoader;
use Puli\Repository\ResourceRepositoryInterface;
use Puli\Tests\Resource\TestDirectory;

/**
 * @since  1.0
 * @author Bernhard Schussek <bschussek@gmail.com>
 */
class PuliTemplateLoaderTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var ResourceRepositoryInterface|\PHPUnit_Framework_MockObject_MockObject
     */
    private $repo;

    /**
     * @var PuliTemplateLoader
     */
    private $loader;

    protected function setUp()
    {
        $this->repo = $this->getMock('Puli\Repository\ResourceRepositoryInterface');
        $this->loader = new PuliTemplateLoader($this->repo);
    }

    /**
     * @expectedException \Twig_Error_Loader
     */
    public function testGetSourceFailsIfNoFileResource()
    {
        $this->repo->expects($this->once())
            ->method('get')
            ->with('/webmozart/puli/file')
            ->will($this->returnValue(new TestDirectory('/webmozart/puli/file')));

        $this->loader->getSource('/webmozart/puli/file');
    }

    /**
     * @expectedException \Twig_Error_Loader
     */
    public function testIsFreshFailsIfNoFileResource()
    {
        $this->repo->expects($this->once())
            ->method('get')
            ->with('/webmozart/puli/file')
            ->will($this->returnValue(new TestDirectory('/webmozart/puli/file')));

        $this->loader->isFresh('/webmozart/puli/file', 123);
    }
}
