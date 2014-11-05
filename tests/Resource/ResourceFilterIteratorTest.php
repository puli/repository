<?php

/*
 * This file is part of the Puli package.
 *
 * (c) Bernhard Schussek <bschussek@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Webmozart\Puli\Tests\Resource;

use Webmozart\Puli\Resource\DirectoryResourceIterator;
use Webmozart\Puli\Resource\ResourceCollection;
use Webmozart\Puli\Resource\ResourceCollectionIterator;
use Webmozart\Puli\Resource\ResourceFilterIterator;

/**
 * @since  1.0
 * @author Bernhard Schussek <bschussek@gmail.com>
 */
class ResourceFilterIteratorTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var ResourceCollection
     */
    private $collection;

    protected function setUp()
    {
        $this->collection = new ResourceCollection(array(
            new TestDirectory('/webmozart', array(
                new TestDirectory('/webmozart/puli', array(
                    new TestDirectory('/webmozart/puli/config', array(
                        new TestFile('/webmozart/puli/config/config.yml'),
                        new TestFile('/webmozart/puli/config/routing.yml'),
                    )),
                    new TestDirectory('/webmozart/puli/css', array(
                        new TestDirectory('/webmozart/puli/css/bootstrap', array(
                            new TestFile('/webmozart/puli/css/bootstrap/bootstrap.css'),
                        )),
                        new TestFile('/webmozart/puli/css/fonts.css'),
                        new TestFile('/webmozart/puli/css/style.css'),
                    )),
                    new TestFile('/webmozart/puli/installer.json'),
                ))
            )),
        ));
    }

    /**
     * @expectedException \InvalidArgumentException
     */
    public function testRejectEmptyPattern()
    {
        $innerIterator = new ResourceCollectionIterator(new ResourceCollection());

        new ResourceFilterIterator($innerIterator, '');
    }

    public function testFilterPathPrefix()
    {
        $iterator = new ResourceFilterIterator(
            new \RecursiveIteratorIterator(
                new ResourceCollectionIterator($this->collection),
                \RecursiveIteratorIterator::SELF_FIRST
            ),
            '/webmozart/puli/css',
            ResourceFilterIterator::MATCH_PREFIX
                | ResourceFilterIterator::CURRENT_AS_PATH
        );

        $expected = array(
            '/webmozart/puli/css' => '/webmozart/puli/css',
            '/webmozart/puli/css/bootstrap' => '/webmozart/puli/css/bootstrap',
            '/webmozart/puli/css/bootstrap/bootstrap.css' => '/webmozart/puli/css/bootstrap/bootstrap.css',
            '/webmozart/puli/css/fonts.css' => '/webmozart/puli/css/fonts.css',
            '/webmozart/puli/css/style.css' => '/webmozart/puli/css/style.css',
        );

        $this->assertSame($expected, iterator_to_array($iterator));
    }

    public function testFilterPathSuffix()
    {
        $iterator = new ResourceFilterIterator(
            new \RecursiveIteratorIterator(
                new ResourceCollectionIterator($this->collection),
                \RecursiveIteratorIterator::SELF_FIRST
            ),
            '.css',
            ResourceFilterIterator::MATCH_SUFFIX
                | ResourceFilterIterator::CURRENT_AS_PATH
        );

        $expected = array(
            '/webmozart/puli/css/bootstrap/bootstrap.css' => '/webmozart/puli/css/bootstrap/bootstrap.css',
            '/webmozart/puli/css/fonts.css' => '/webmozart/puli/css/fonts.css',
            '/webmozart/puli/css/style.css' => '/webmozart/puli/css/style.css',
        );

        $this->assertSame($expected, iterator_to_array($iterator));
    }

    public function testFilterPathRegexImplicit()
    {
        $iterator = new ResourceFilterIterator(
            new \RecursiveIteratorIterator(
                new ResourceCollectionIterator($this->collection),
                \RecursiveIteratorIterator::SELF_FIRST
            ),
            '/\.css$/',
            ResourceFilterIterator::CURRENT_AS_PATH
        );

        $expected = array(
            '/webmozart/puli/css/bootstrap/bootstrap.css' => '/webmozart/puli/css/bootstrap/bootstrap.css',
            '/webmozart/puli/css/fonts.css' => '/webmozart/puli/css/fonts.css',
            '/webmozart/puli/css/style.css' => '/webmozart/puli/css/style.css',
        );

        $this->assertSame($expected, iterator_to_array($iterator));
    }

    public function testFilterPathRegexExplicit()
    {
        $iterator = new ResourceFilterIterator(
            new \RecursiveIteratorIterator(
                new ResourceCollectionIterator($this->collection),
                \RecursiveIteratorIterator::SELF_FIRST
            ),
            '/\.css$/',
            ResourceFilterIterator::MATCH_REGEX
                | ResourceFilterIterator::CURRENT_AS_PATH
        );

        $expected = array(
            '/webmozart/puli/css/bootstrap/bootstrap.css' => '/webmozart/puli/css/bootstrap/bootstrap.css',
            '/webmozart/puli/css/fonts.css' => '/webmozart/puli/css/fonts.css',
            '/webmozart/puli/css/style.css' => '/webmozart/puli/css/style.css',
        );

        $this->assertSame($expected, iterator_to_array($iterator));
    }

    public function testFilterNamePrefix()
    {
        $iterator = new ResourceFilterIterator(
            new \RecursiveIteratorIterator(
                new ResourceCollectionIterator($this->collection),
                \RecursiveIteratorIterator::SELF_FIRST
            ),
            'bootstrap',
            ResourceFilterIterator::FILTER_BY_NAME
                | ResourceFilterIterator::MATCH_PREFIX
                | ResourceFilterIterator::CURRENT_AS_PATH
        );

        $expected = array(
            '/webmozart/puli/css/bootstrap' => '/webmozart/puli/css/bootstrap',
            '/webmozart/puli/css/bootstrap/bootstrap.css' => '/webmozart/puli/css/bootstrap/bootstrap.css',
        );

        $this->assertSame($expected, iterator_to_array($iterator));
    }

    /**
     * @depends testFilterPathPrefix
     */
    public function testCurrentAsResourceImplicit()
    {
        $iterator = new ResourceFilterIterator(
            new \RecursiveIteratorIterator(
                new ResourceCollectionIterator($this->collection),
                \RecursiveIteratorIterator::SELF_FIRST
            ),
            '/webmozart/puli/css',
            ResourceFilterIterator::MATCH_PREFIX
        );

        /** @var TestDirectory $puli */
        $puli = $this->collection->get(0)->get('puli');

        $expected = array(
            '/webmozart/puli/css' => $puli->get('css'),
            '/webmozart/puli/css/bootstrap' => $puli->get('css')->get('bootstrap'),
            '/webmozart/puli/css/bootstrap/bootstrap.css' => $puli->get('css')->get('bootstrap')->get('bootstrap.css'),
            '/webmozart/puli/css/fonts.css' => $puli->get('css')->get('fonts.css'),
            '/webmozart/puli/css/style.css' => $puli->get('css')->get('style.css'),
        );

        $this->assertEquals($expected, iterator_to_array($iterator));
    }

    /**
     * @depends testFilterPathPrefix
     */
    public function testCurrentAsResourceExplicit()
    {
        $iterator = new ResourceFilterIterator(
            new \RecursiveIteratorIterator(
                new ResourceCollectionIterator($this->collection),
                \RecursiveIteratorIterator::SELF_FIRST
            ),
            '/webmozart/puli/css',
            ResourceFilterIterator::MATCH_PREFIX
                | ResourceFilterIterator::CURRENT_AS_RESOURCE
        );

        /** @var TestDirectory $puli */
        $puli = $this->collection->get(0)->get('puli');

        $expected = array(
            '/webmozart/puli/css' => $puli->get('css'),
            '/webmozart/puli/css/bootstrap' => $puli->get('css')->get('bootstrap'),
            '/webmozart/puli/css/bootstrap/bootstrap.css' => $puli->get('css')->get('bootstrap')->get('bootstrap.css'),
            '/webmozart/puli/css/fonts.css' => $puli->get('css')->get('fonts.css'),
            '/webmozart/puli/css/style.css' => $puli->get('css')->get('style.css'),
        );

        $this->assertSame($expected, iterator_to_array($iterator));
    }

    /**
     * @depends testFilterPathPrefix
     */
    public function testCurrentAsNamePath()
    {
        $iterator = new ResourceFilterIterator(
            new \RecursiveIteratorIterator(
                new ResourceCollectionIterator($this->collection),
                \RecursiveIteratorIterator::SELF_FIRST
            ),
            '/webmozart/puli/css',
            ResourceFilterIterator::MATCH_PREFIX
                | ResourceFilterIterator::CURRENT_AS_NAME
        );

        $expected = array(
            '/webmozart/puli/css' => 'css',
            '/webmozart/puli/css/bootstrap' => 'bootstrap',
            '/webmozart/puli/css/bootstrap/bootstrap.css' => 'bootstrap.css',
            '/webmozart/puli/css/fonts.css' => 'fonts.css',
            '/webmozart/puli/css/style.css' => 'style.css',
        );

        $this->assertSame($expected, iterator_to_array($iterator));
    }

    /**
     * @depends testFilterPathPrefix
     */
    public function testKeyAsPath()
    {
        $iterator = new ResourceFilterIterator(
            new \RecursiveIteratorIterator(
                new ResourceCollectionIterator($this->collection),
                \RecursiveIteratorIterator::SELF_FIRST
            ),
            '/webmozart/puli/css',
            ResourceFilterIterator::MATCH_PREFIX
                | ResourceFilterIterator::CURRENT_AS_PATH
                | ResourceFilterIterator::KEY_AS_PATH
        );

        $expected = array(
            '/webmozart/puli/css' => '/webmozart/puli/css',
            '/webmozart/puli/css/bootstrap' => '/webmozart/puli/css/bootstrap',
            '/webmozart/puli/css/bootstrap/bootstrap.css' => '/webmozart/puli/css/bootstrap/bootstrap.css',
            '/webmozart/puli/css/fonts.css' => '/webmozart/puli/css/fonts.css',
            '/webmozart/puli/css/style.css' => '/webmozart/puli/css/style.css',
        );

        $this->assertSame($expected, iterator_to_array($iterator));
    }

    /**
     * @depends testFilterPathPrefix
     */
    public function testKeyAsCursor()
    {
        $iterator = new ResourceFilterIterator(
            new DirectoryResourceIterator($this->collection->get(0)->get('puli')->get('css')),
            '.css',
            ResourceFilterIterator::MATCH_SUFFIX
                | ResourceFilterIterator::CURRENT_AS_PATH
                | ResourceFilterIterator::KEY_AS_CURSOR
        );

        $expected = array(
            0 => '/webmozart/puli/css/fonts.css',
            1 => '/webmozart/puli/css/style.css',
        );

        $this->assertSame($expected, iterator_to_array($iterator));
    }
}
