<?php

/*
 * This file is part of the Puli package.
 *
 * (c) Bernhard Schussek <bschussek@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Puli\Tests\Extension\Assetic\Twig;

use Assetic\Extension\Twig\AsseticExtension;
use Puli\Extension\Assetic\Factory\PuliAssetFactory;
use Puli\Extension\Assetic\Twig\PuliAsseticExtension;
use Puli\Extension\Twig\PuliExtension;
use Puli\Extension\Twig\PuliTemplateLoader;
use Puli\ResourceRepository;
use Puli\Tests\Extension\Twig\RandomizedTwigEnvironment;

/**
 * @since  1.0
 * @author Bernhard Schussek <bschussek@gmail.com>
 */
class PuliAsseticExtensionTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var RandomizedTwigEnvironment
     */
    private $twig;

    /**
     * @var PuliAssetFactory
     */
    private $assetFactory;

    /**
     * @var ResourceRepository
     */
    private $repo;

    protected function setUp()
    {
        $this->repo = new ResourceRepository();
        $this->repo->add('/webmozart/puli', __DIR__.'/Fixtures');

        $this->assetFactory = new PuliAssetFactory($this->repo);

        $this->twig = new RandomizedTwigEnvironment(new PuliTemplateLoader($this->repo));
        $this->twig->addExtension(new AsseticExtension($this->assetFactory, array(), null, true));
        $this->twig->addExtension(new PuliExtension($this->repo));
        $this->twig->addExtension(new PuliAsseticExtension($this->repo));
    }

    public function provideTemplates()
    {
        return array(
            array(
                '/webmozart/puli/views/stylesheet-absolute.html.twig',
                '<link href="css/0ed17b6.css" rel="stylesheet" media="screen" />'."\n",
            ),
            array(
                '/webmozart/puli/views/stylesheet-relative.html.twig',
                // The generated name must be the same as for the absolute path
                '<link href="css/0ed17b6.css" rel="stylesheet" media="screen" />'."\n",
            ),
            array(
                '/webmozart/puli/views/stylesheet-custom-name.html.twig',
                '<link href="css/style.css" rel="stylesheet" media="screen" />'."\n",
            ),
            array(
                '/webmozart/puli/views/stylesheet-custom-output.html.twig',
                '<link href="css/puli/style.css" rel="stylesheet" media="screen" />'."\n",
            ),
            array(
                '/webmozart/puli/views/stylesheet-multiple.html.twig',
                '<link href="css/6507399.css" rel="stylesheet" media="screen" />'."\n",
            ),
            array(
                '/webmozart/puli/views/stylesheet-multiple.html.twig',
                '<link href="css/6507399_style_1.css" rel="stylesheet" media="screen" />'."\n".
                '<link href="css/6507399_reset_2.css" rel="stylesheet" media="screen" />'."\n",
                true,
            ),
            array(
                '/webmozart/puli/views/javascript-absolute.html.twig',
                '<script src="js/9459472.js"></script>'."\n",
            ),
            array(
                '/webmozart/puli/views/javascript-relative.html.twig',
                // The generated name must be the same as for the absolute path
                '<script src="js/9459472.js"></script>'."\n",
            ),
            array(
                '/webmozart/puli/views/javascript-custom-name.html.twig',
                '<script src="js/script.js"></script>'."\n",
            ),
            array(
                '/webmozart/puli/views/javascript-custom-output.html.twig',
                '<script src="js/puli/script.js"></script>'."\n",
            ),
            array(
                '/webmozart/puli/views/javascript-multiple.html.twig',
                '<script src="js/63ece2e.js"></script>'."\n",
            ),
            array(
                '/webmozart/puli/views/javascript-multiple.html.twig',
                '<script src="js/63ece2e_script_1.js"></script>'."\n".
                '<script src="js/63ece2e_iefix_2.js"></script>'."\n",
                true,
            ),
            array(
                '/webmozart/puli/views/image-absolute.html.twig',
                '<img src="images/7967e7f.gif" />'."\n",
            ),
            array(
                '/webmozart/puli/views/image-relative.html.twig',
                // The generated name must be the same as for the absolute path
                '<img src="images/7967e7f.gif" />'."\n",
            ),
            array(
                '/webmozart/puli/views/image-custom-name.html.twig',
                '<img src="images/banana.gif" />'."\n",
            ),
            array(
                '/webmozart/puli/views/image-custom-output.html.twig',
                '<img src="images/puli/banana.gif" />'."\n",
            ),
        );
    }

    /**
     * @dataProvider provideTemplates
     */
    public function testRendering($template, $output, $debug = false)
    {
        $this->assetFactory->setDebug($debug);

        $this->assertSame($output, $this->twig->render($template));
    }

    public function testImageAbsolutePath()
    {
        $this->assertSame(
            '<img src="images/7967e7f.gif" />'."\n",
            $this->twig->render('/webmozart/puli/views/image-absolute.html.twig')
        );
    }

    public function testImageRelativePath()
    {
        $this->assertSame(
            // The generated name must be the same as for the absolute path
            '<img src="images/7967e7f.gif" />'."\n",
            $this->twig->render('/webmozart/puli/views/image-absolute.html.twig')
        );
    }
}
