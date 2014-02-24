<?php

/*
 * This file is part of the Puli package.
 *
 * (c) Bernhard Schussek <bschussek@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Webmozart\Puli\Tests\Extension\Assetic\Factory;

use Assetic\Asset\AssetReference;
use Assetic\Asset\FileAsset;
use Assetic\Asset\HttpAsset;
use Assetic\AssetManager;
use Webmozart\Puli\Extension\Assetic\Asset\PuliAsset;
use Webmozart\Puli\Extension\Assetic\Factory\PuliAssetFactory;
use Webmozart\Puli\Locator\UriLocator;
use Webmozart\Puli\Repository\ResourceRepository;

/**
 * @since  1.0
 * @author Bernhard Schussek <bschussek@gmail.com>
 */
class PuliAssetFactoryTest extends \PHPUnit_Framework_TestCase
{
    private static $fixturesDir;

    /**
     * @var ResourceRepository
     */
    private $repo;

    /**
     * @var PuliAssetFactory
     */
    private $factory;

    public static function setUpBeforeClass()
    {
        self::$fixturesDir = __DIR__.'/Fixtures';
    }

    protected function setUp()
    {
        $this->repo = new ResourceRepository();
        $this->repo->add('/webmozart/puli', self::$fixturesDir);
        $this->factory = new PuliAssetFactory($this->repo);
    }

    public function testCreatePuliAsset()
    {
        $collection = $this->factory->createAsset(
            array('/webmozart/puli/css/style.css'),
            array(),
            array('vars' => array('var' => 'value'))
        );

        $assets = iterator_to_array($collection);

        /** @var PuliAsset[] $assets */
        $this->assertCount(1, $assets);
        $this->assertInstanceOf('Webmozart\Puli\Extension\Assetic\Asset\PuliAsset', $assets[0]);
        $this->assertSame('/', $assets[0]->getSourceRoot());
        $this->assertSame('/webmozart/puli/css/style.css', $assets[0]->getSourcePath());
        $this->assertSame(array('var' => 'value'), $assets[0]->getVars());
    }

    public function testCreatePuliAssetCollection()
    {
        $collection = $this->factory->createAsset(
            array('/webmozart/puli/css/*.css'),
            array(),
            array('vars' => array('var' => 'value'))
        );

        $this->assertSame(array('var' => 'value'), $collection->getVars());

        $assets = iterator_to_array($collection);

        /** @var PuliAsset[] $assets */
        $this->assertCount(2, $assets);
        $this->assertInstanceOf('Webmozart\Puli\Extension\Assetic\Asset\PuliAsset', $assets[0]);
        $this->assertSame('/', $assets[0]->getSourceRoot());
        $this->assertSame('/webmozart/puli/css/reset.css', $assets[0]->getSourcePath());
        $this->assertSame(array(), $assets[0]->getVars());
        $this->assertInstanceOf('Webmozart\Puli\Extension\Assetic\Asset\PuliAsset', $assets[1]);
        $this->assertSame('/', $assets[1]->getSourceRoot());
        $this->assertSame('/webmozart/puli/css/style.css', $assets[1]->getSourcePath());
        $this->assertSame(array(), $assets[1]->getVars());
    }

    public function testCreateFileAsset()
    {
        $collection = $this->factory->createAsset(
            array(self::$fixturesDir.'/css/style.css'),
            array(),
            array('vars' => array('var' => 'value'))
        );

        $assets = iterator_to_array($collection);

        /** @var FileAsset[] $assets */
        $this->assertCount(1, $assets);
        $this->assertInstanceOf('Assetic\Asset\FileAsset', $assets[0]);
        $this->assertSame(self::$fixturesDir.'/css', $assets[0]->getSourceRoot());
        $this->assertSame('style.css', $assets[0]->getSourcePath());
        $this->assertSame(array('var' => 'value'), $assets[0]->getVars());
    }

    public function getHttpUrls()
    {
        return array(
            array('http://example.com/foo.css', 'http://example.com', 'foo.css'),
            array('https://example.com/foo.css', 'https://example.com', 'foo.css'),
            array('//example.com/foo.css', 'http://example.com', 'foo.css'),
        );
    }

    /**
     * @dataProvider getHttpUrls
     */
    public function testCreateHttpAsset($sourceUrl, $sourceRoot, $sourcePath)
    {
        $collection = $this->factory->createAsset(
            array($sourceUrl),
            array(),
            array('vars' => array('var' => 'value'))
        );

        $assets = iterator_to_array($collection);

        /** @var HttpAsset[] $assets */
        $this->assertCount(1, $assets);
        $this->assertInstanceOf('Assetic\Asset\HttpAsset', $assets[0]);
        $this->assertSame($sourceRoot, $assets[0]->getSourceRoot());
        $this->assertSame($sourcePath, $assets[0]->getSourcePath());
        $this->assertSame(array('var' => 'value'), $assets[0]->getVars());
    }

    /**
     * @dataProvider getHttpUrls
     */
    public function testCreateHttpAssetWithUriLocator($sourceUrl, $sourceRoot, $sourcePath)
    {
        $uriLocator = new UriLocator();
        $uriLocator->register('resource', $this->repo);
        $this->factory = new PuliAssetFactory($uriLocator);

        $collection = $this->factory->createAsset(
            array($sourceUrl),
            array(),
            array('vars' => array('var' => 'value'))
        );

        $assets = iterator_to_array($collection);

        /** @var HttpAsset[] $assets */
        $this->assertCount(1, $assets);
        $this->assertInstanceOf('Assetic\Asset\HttpAsset', $assets[0]);
        $this->assertSame($sourceRoot, $assets[0]->getSourceRoot());
        $this->assertSame($sourcePath, $assets[0]->getSourcePath());
        $this->assertSame(array('var' => 'value'), $assets[0]->getVars());
    }

    public function testCreatePuliUriAsset()
    {
        $uriLocator = new UriLocator();
        $uriLocator->register('resource', $this->repo);
        $this->factory = new PuliAssetFactory($uriLocator);

        $collection = $this->factory->createAsset(
            array('resource:///webmozart/puli/css/style.css'),
            array(),
            array('vars' => array('var' => 'value'))
        );

        $assets = iterator_to_array($collection);

        /** @var FileAsset[] $assets */
        $this->assertCount(1, $assets);
        $this->assertInstanceOf('Assetic\Asset\FileAsset', $assets[0]);
        $this->assertSame('/', $assets[0]->getSourceRoot());
        $this->assertSame('/webmozart/puli/css/style.css', $assets[0]->getSourcePath());
        $this->assertSame(array('var' => 'value'), $assets[0]->getVars());
    }

    public function testCreateAssetReference()
    {
        $reference = $this->getMock('Assetic\Asset\AssetInterface');
        $am = new AssetManager();
        $am->set('reference', $reference);

        $this->factory->setAssetManager($am);

        $collection = $this->factory->createAsset(array('@reference'));

        $assets = iterator_to_array($collection);

        /** @var AssetReference[] $assets */
        $this->assertCount(1, $assets);
        $this->assertInstanceOf('Assetic\Asset\AssetReference', $assets[0]);
    }
}
