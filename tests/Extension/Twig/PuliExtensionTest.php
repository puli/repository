<?php

/*
 * This file is part of the Puli package.
 *
 * (c) Bernhard Schussek <bschussek@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Webmozart\Puli\Tests\Extension\Twig;

use Webmozart\Puli\Extension\Twig\PuliExtension;
use Webmozart\Puli\Extension\Twig\PuliTemplateLoader;
use Webmozart\Puli\Repository\ResourceRepository;

/**
 * @since  1.0
 * @author Bernhard Schussek <bschussek@gmail.com>
 */
class PuliExtensionTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var RandomizedTwigEnvironment
     */
    private $twig;

    /**
     * @var ResourceRepository
     */
    private $repo;

    protected function setUp()
    {
        $this->repo = new ResourceRepository();
        $this->repo->add('/acme/blog/views', __DIR__.'/Fixtures/puli');

        $this->twig = new RandomizedTwigEnvironment(new \Twig_Loader_Chain(array(
            new PuliTemplateLoader($this->repo),
            new \Twig_Loader_Filesystem(__DIR__.'/Fixtures'),
        )));
        $this->twig->addExtension(new PuliExtension($this->repo));
    }

    public function testRender()
    {
        $this->assertSame(
            "TEMPLATE\n",
            $this->twig->render('/acme/blog/views/template.txt.twig')
        );
    }

    public function testExtendAbsolutePath()
    {
        $this->assertSame(
            "PARENT\n\nCHILD\n",
            $this->twig->render('/acme/blog/views/extend-absolute.txt.twig')
        );
    }

    public function testExtendRelativePath()
    {
        $this->assertSame(
            "PARENT\n\nCHILD\n",
            $this->twig->render('/acme/blog/views/extend-relative.txt.twig')
        );
    }

    public function testExtendRelativeDotDotPath()
    {
        $this->assertSame(
            "PARENT\n\nCHILD\n",
            $this->twig->render('/acme/blog/views/nested/extend-relative-dot-dot.txt.twig')
        );
    }

    public function testIncludeAbsolutePath()
    {
        $this->assertSame(
            "TEMPLATE\n\nREFERENCE\n",
            $this->twig->render('/acme/blog/views/include-absolute.txt.twig')
        );
    }

    public function testIncludeRelativePath()
    {
        $this->assertSame(
            "TEMPLATE\n\nREFERENCE\n",
            $this->twig->render('/acme/blog/views/include-relative.txt.twig')
        );
    }

    public function testIncludeNonPuliAndRelativePath()
    {
        // Resolution of relative paths should work after including a template
        // with a different loader than PuliTemplateLoader
        $this->assertSame(
            "TEMPLATE\n\nNON PULI REFERENCE\n\nREFERENCE\n",
            $this->twig->render('/acme/blog/views/include-non-puli-and-relative.txt.twig')
        );
    }

    public function testEmbedAbsolutePath()
    {
        $this->assertSame(
            "TEMPLATE\n\nREFERENCE\n",
            $this->twig->render('/acme/blog/views/embed-absolute.txt.twig')
        );
    }

    public function testEmbedRelativePath()
    {
        $this->assertSame(
            "TEMPLATE\n\nREFERENCE\n",
            $this->twig->render('/acme/blog/views/embed-relative.txt.twig')
        );
    }

    public function testExtendAbsolutePathNonPuli()
    {
        $this->assertSame(
            "PARENT\n\nCHILD\n",
            $this->twig->render('/non-puli/extend-absolute.txt.twig')
        );
    }

    /**
     * @expectedException \Twig_Error_Loader
     */
    public function testExtendRelativePathNonPuli()
    {
        $this->twig->render('/non-puli/extend-relative.txt.twig');
    }

    public function testIncludeWhichExtendsAbsolutePathNonPuli()
    {
        $this->assertSame(
            "TEMPLATE\n\nPARENT\n\nCHILD\n",
            $this->twig->render('/non-puli/include-extend-absolute.txt.twig')
        );
    }

    /**
     * @expectedException \Twig_Error_Loader
     */
    public function testIncludeWhichExtendsRelativePathNonPuli()
    {
        $this->twig->render('/non-puli/include-extend-relative.txt.twig');
    }
}
