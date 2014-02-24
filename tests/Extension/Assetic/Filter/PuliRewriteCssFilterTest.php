<?php

/*
 * This file is part of the Puli package.
 *
 * (c) Bernhard Schussek <bschussek@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Webmozart\Puli\Tests\Extension\Assetic\Filter;

use Assetic\Asset\StringAsset;
use Assetic\AssetManager;
use Webmozart\Puli\Extension\Assetic\Asset\PuliStringAsset;
use Webmozart\Puli\Extension\Assetic\Filter\PuliRewriteCssFilter;

/**
 * @since  1.0
 * @author Bernhard Schussek <bschussek@gmail.com>
 */
class PuliRewriteCssFilterTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var AssetManager
     */
    private $am;

    /**
     * @var PuliRewriteCssFilter
     */
    private $filter;

    protected function setUp()
    {
        $this->am = new AssetManager();
        $this->filter = new PuliRewriteCssFilter($this->am);
    }

    public function provideUrls()
    {
        return array(
            // url variants
            array(
                'body { background: url(%s); }',
                '/webmozart/puli/css/style.css', 'css/style.css',
                '/acme/theme/images/background.png', 'images/bg.png',
                '/acme/theme/images/background.png', '../images/bg.png'
            ),
            array(
                'body { background: url("%s"); }',
                '/webmozart/puli/css/style.css', 'css/style.css',
                '/acme/theme/images/background.png', 'images/bg.png',
                '/acme/theme/images/background.png', '../images/bg.png'
            ),
            array(
                'body { background: url(\'%s\'); }',
                '/webmozart/puli/css/style.css', 'css/style.css',
                '/acme/theme/images/background.png', 'images/bg.png',
                '/acme/theme/images/background.png', '../images/bg.png'
            ),

            // absolute target paths
            array(
                'body { background: url(%s); }',
                '/webmozart/puli/css/style.css', '/css/style.css',
                '/acme/theme/images/background.png', '/images/bg.png',
                '/acme/theme/images/background.png', '../images/bg.png'
            ),

            // relative target paths are treated like absolute paths
            array(
                'body { background: url(%s); }',
                '/webmozart/puli/css/style.css', 'css/style.css',
                '/acme/theme/images/background.png', '/images/bg.png',
                '/acme/theme/images/background.png', '../images/bg.png'
            ),
            array(
                'body { background: url(%s); }',
                '/webmozart/puli/css/style.css', '/css/style.css',
                '/acme/theme/images/background.png', 'images/bg.png',
                '/acme/theme/images/background.png', '../images/bg.png'
            ),

            // target path in root
            array(
                'body { background: url(%s); }',
                '/webmozart/puli/css/style.css', 'style.css',
                '/acme/theme/images/background.png', 'images/bg.png',
                '/acme/theme/images/background.png', 'images/bg.png'
            ),

            // relative repository paths
            array(
                'body { background: url(%s); }',
                '/webmozart/puli/css/style.css', 'css/style.css',
                '/webmozart/puli/css/background.png', 'images/bg.png',
                'background.png', '../images/bg.png'
            ),
            array(
                'body { background: url(%s); }',
                '/webmozart/puli/css/style.css', 'css/style.css',
                '/webmozart/puli/images/background.png', 'images/bg.png',
                '../images/background.png', '../images/bg.png'
            ),

            // url with data
            array(
                'body { background: url(%s); }',
                '/webmozart/puli/css/style.css', 'css/style.css',
                '/acme/theme/images/background.png', 'images/bg.png',
                'data:image/png;base64,abcdef=', 'data:image/png;base64,abcdef='
            ),
            array(
                'body { background: url(%s); }',
                '/webmozart/puli/css/style.css', 'css/style.css',
                '/acme/theme/images/data:bg.png', 'images/bg.png',
                '/acme/theme/images/data:bg.png', '../images/bg.png'
            ),

            // @import variants
            array(
                '@import "%s";',
                '/webmozart/puli/css/style.css', 'css/style.css',
                '/acme/theme/images/background.png', 'images/bg.png',
                '/acme/theme/images/background.png', '../images/bg.png'
            ),
            array(
                '@import url(%s);',
                '/webmozart/puli/css/style.css', 'css/style.css',
                '/acme/theme/images/background.png', 'images/bg.png',
                '/acme/theme/images/background.png', '../images/bg.png'
            ),
            array(
                '@import url("%s");',
                '/webmozart/puli/css/style.css', 'css/style.css',
                '/acme/theme/images/background.png', 'images/bg.png',
                '/acme/theme/images/background.png', '../images/bg.png'
            ),
            array(
                '@import url(\'%s\');',
                '/webmozart/puli/css/style.css', 'css/style.css',
                '/acme/theme/images/background.png', 'images/bg.png',
                '/acme/theme/images/background.png', '../images/bg.png'
            ),

            // @font-face
            array(
                '@font-face { src: url(%s); }',
                '/webmozart/puli/css/style.css', 'css/style.css',
                '/twitter/bootstrap/fonts/glyphicons.ttf', 'fonts/glyphicons.ttf',
                '/twitter/bootstrap/fonts/glyphicons.ttf', '../fonts/glyphicons.ttf'
            ),

            // IE AlphaImageLoader filter
            array(
                '.fix { filter: progid:DXImageTransform.Microsoft.AlphaImageLoader(src=\'%s\'); }',
                '/webmozart/puli/css/ie.css', 'css/ie.css',
                '/webmozart/puli/images/fix.png', 'images/fix.png',
                '../images/fix.png', '../images/fix.png'
            ),
        );
    }

    /**
     * @dataProvider provideUrls
     */
    public function testUrls($format, $path, $targetPath, $path2, $targetPath2, $inputUrl, $expectedUrl)
    {
        $asset = new PuliStringAsset($path, sprintf($format, $inputUrl));
        $asset->setTargetPath($targetPath);
        $asset->load();

        $asset2 = new PuliStringAsset($path2, null);
        $asset2->setTargetPath($targetPath2);

        $this->am->set('asset2', $asset2);

        $this->filter->filterLoad($asset);
        $this->filter->filterDump($asset);

        $this->assertEquals(sprintf($format, $expectedUrl), $asset->getContent());
    }

    public function testIgnoreNonPuliAssets()
    {
        $asset = new StringAsset('content');
        $asset->load();

        $this->filter->filterLoad($asset);
        $this->filter->filterDump($asset);

        // no error
    }

    /**
     * @expectedException \Webmozart\Puli\Extension\Assetic\AssetException
     */
    public function testTargetAssetMustExist()
    {
        $asset = new PuliStringAsset('/css/style.css', 'body { background: url(/images/bg.png); }');
        $asset->load();

        $this->filter->filterLoad($asset);
        $this->filter->filterDump($asset);
    }

    /**
     * @expectedException \Webmozart\Puli\Extension\Assetic\AssetException
     */
    public function testTargetPathMustBeSet()
    {
        $asset = new PuliStringAsset('/css/style.css', 'body { background: url(/images/bg.png); }');
        $asset->load();

        $asset2 = new PuliStringAsset('/images/bg.png', null);
        $asset2->setTargetPath('/images/bg.png');

        $this->am->set('asset2', $asset2);

        $this->filter->filterLoad($asset);
        $this->filter->filterDump($asset);
    }

    public function testDontFailIfNoTargetPathAndNoUrl()
    {
        $asset = new PuliStringAsset('/css/style.css', 'body { background: blue; }');
        $asset->load();

        $asset2 = new PuliStringAsset('/images/bg.png', null);
        $asset2->setTargetPath('/images/bg.png');

        $this->am->set('asset2', $asset2);

        $this->filter->filterLoad($asset);
        $this->filter->filterDump($asset);
    }

    /**
     * @expectedException \Webmozart\Puli\Extension\Assetic\AssetException
     */
    public function testReferencedAssetMustHaveTargetPath()
    {
        $asset = new PuliStringAsset('/css/style.css', 'body { background: url(/images/bg.png); }');
        $asset->setTargetPath('/css/style.css');
        $asset->load();

        $asset2 = new PuliStringAsset('/images/bg.png', null);

        $this->am->set('asset2', $asset2);

        $this->filter->filterLoad($asset);
        $this->filter->filterDump($asset);
    }

    public function testDontFailIfNoTargetPathForReferenceAndNoUrl()
    {
        $asset = new PuliStringAsset('/css/style.css', 'body { background: blue; }');
        $asset->setTargetPath('/css/style.css');
        $asset->load();

        $asset2 = new PuliStringAsset('/images/bg.png', null);

        $this->am->set('asset2', $asset2);

        $this->filter->filterLoad($asset);
        $this->filter->filterDump($asset);
    }

    public function testIgnoreExternalSource()
    {
        $asset = new PuliStringAsset('/css/style.css', 'body { background: url("http://example.com/bg.gif"); }');
        $asset->load();

        $this->filter->filterLoad($asset);
        $this->filter->filterDump($asset);

        $this->assertSame('body { background: url("http://example.com/bg.gif"); }', $asset->getContent());
    }

    public function testIgnoreEmptyUrl()
    {
        $asset = new PuliStringAsset('/css/style.css', 'body { background: url(); }');
        $asset->load();

        $this->filter->filterLoad($asset);
        $this->filter->filterDump($asset);

        $this->assertSame('body { background: url(); }', $asset->getContent());
    }

    public function testIgnoreEmptySrcAttributeSelector()
    {
        $asset = new PuliStringAsset('/css/style.css', 'img[src=""] { border: red; }');
        $asset->load();

        $this->filter->filterLoad($asset);
        $this->filter->filterDump($asset);

        $this->assertSame('img[src=""] { border: red; }', $asset->getContent());
    }
}
