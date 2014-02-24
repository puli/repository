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

use Webmozart\Puli\Repository\ResourceRepository;
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
     * @expectedException \InvalidArgumentException
     */
    public function testRejectEmptyPattern()
    {
        $innerIterator = new ResourceCollectionIterator(new ResourceCollection());

        new ResourceFilterIterator($innerIterator, '');
    }

    public function testFilterPathPrefix()
    {
        $repo = new ResourceRepository();
        $repo->add('/webmozart/puli', __DIR__.'/Fixtures');

        $iterator = new ResourceFilterIterator(
            new \RecursiveIteratorIterator(
                new DirectoryResourceIterator($repo->get('/')),
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
            '/webmozart/puli/css/reset.css' => '/webmozart/puli/css/reset.css',
            '/webmozart/puli/css/style.css' => '/webmozart/puli/css/style.css',
        );

        $this->assertSame($expected, iterator_to_array($iterator));
    }

    public function testFilterPathSuffix()
    {
        $repo = new ResourceRepository();
        $repo->add('/webmozart/puli', __DIR__.'/Fixtures');

        $iterator = new ResourceFilterIterator(
            new \RecursiveIteratorIterator(
                new DirectoryResourceIterator($repo->get('/')),
                \RecursiveIteratorIterator::SELF_FIRST
            ),
            '.css',
            ResourceFilterIterator::MATCH_SUFFIX
                | ResourceFilterIterator::CURRENT_AS_PATH
        );

        $expected = array(
            '/webmozart/puli/css/bootstrap/bootstrap.css' => '/webmozart/puli/css/bootstrap/bootstrap.css',
            '/webmozart/puli/css/fonts.css' => '/webmozart/puli/css/fonts.css',
            '/webmozart/puli/css/reset.css' => '/webmozart/puli/css/reset.css',
            '/webmozart/puli/css/style.css' => '/webmozart/puli/css/style.css',
        );

        $this->assertSame($expected, iterator_to_array($iterator));
    }

    public function testFilterPathRegexImplicit()
    {
        $repo = new ResourceRepository();
        $repo->add('/webmozart/puli', __DIR__.'/Fixtures');

        $iterator = new ResourceFilterIterator(
            new \RecursiveIteratorIterator(
                new DirectoryResourceIterator($repo->get('/')),
                \RecursiveIteratorIterator::SELF_FIRST
            ),
            '/\.css$/',
            ResourceFilterIterator::CURRENT_AS_PATH
        );

        $expected = array(
            '/webmozart/puli/css/bootstrap/bootstrap.css' => '/webmozart/puli/css/bootstrap/bootstrap.css',
            '/webmozart/puli/css/fonts.css' => '/webmozart/puli/css/fonts.css',
            '/webmozart/puli/css/reset.css' => '/webmozart/puli/css/reset.css',
            '/webmozart/puli/css/style.css' => '/webmozart/puli/css/style.css',
        );

        $this->assertSame($expected, iterator_to_array($iterator));
    }

    public function testFilterPathRegexExplicit()
    {
        $repo = new ResourceRepository();
        $repo->add('/webmozart/puli', __DIR__.'/Fixtures');

        $iterator = new ResourceFilterIterator(
            new \RecursiveIteratorIterator(
                new DirectoryResourceIterator($repo->get('/')),
                \RecursiveIteratorIterator::SELF_FIRST
            ),
            '/\.css$/',
            ResourceFilterIterator::MATCH_REGEX
                | ResourceFilterIterator::CURRENT_AS_PATH
        );

        $expected = array(
            '/webmozart/puli/css/bootstrap/bootstrap.css' => '/webmozart/puli/css/bootstrap/bootstrap.css',
            '/webmozart/puli/css/fonts.css' => '/webmozart/puli/css/fonts.css',
            '/webmozart/puli/css/reset.css' => '/webmozart/puli/css/reset.css',
            '/webmozart/puli/css/style.css' => '/webmozart/puli/css/style.css',
        );

        $this->assertSame($expected, iterator_to_array($iterator));
    }

    public function testFilterRealPathPrefix()
    {
        $repo = new ResourceRepository();
        $repo->add('/webmozart/puli', __DIR__.'/Fixtures');

        $iterator = new ResourceFilterIterator(
            new \RecursiveIteratorIterator(
                new DirectoryResourceIterator($repo->get('/')),
                \RecursiveIteratorIterator::SELF_FIRST
            ),
            __DIR__.'/Fixtures/css',
            ResourceFilterIterator::FILTER_BY_REAL_PATH
                | ResourceFilterIterator::MATCH_PREFIX
                | ResourceFilterIterator::CURRENT_AS_PATH
        );

        $expected = array(
            '/webmozart/puli/css' => '/webmozart/puli/css',
            '/webmozart/puli/css/bootstrap' => '/webmozart/puli/css/bootstrap',
            '/webmozart/puli/css/bootstrap/bootstrap.css' => '/webmozart/puli/css/bootstrap/bootstrap.css',
            '/webmozart/puli/css/fonts.css' => '/webmozart/puli/css/fonts.css',
            '/webmozart/puli/css/reset.css' => '/webmozart/puli/css/reset.css',
            '/webmozart/puli/css/style.css' => '/webmozart/puli/css/style.css',
        );

        $this->assertSame($expected, iterator_to_array($iterator));
    }

    public function testFilterNamePrefix()
    {
        $repo = new ResourceRepository();
        $repo->add('/webmozart/puli', __DIR__.'/Fixtures');

        $iterator = new ResourceFilterIterator(
            new \RecursiveIteratorIterator(
                new DirectoryResourceIterator($repo->get('/')),
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
        $repo = new ResourceRepository();
        $repo->add('/webmozart/puli', __DIR__.'/Fixtures');

        $iterator = new ResourceFilterIterator(
            new \RecursiveIteratorIterator(
                new DirectoryResourceIterator($repo->get('/')),
                \RecursiveIteratorIterator::SELF_FIRST
            ),
            '/webmozart/puli/css',
            ResourceFilterIterator::MATCH_PREFIX
        );

        $expected = array(
            '/webmozart/puli/css' => $repo->get('/webmozart/puli/css'),
            '/webmozart/puli/css/bootstrap' => $repo->get('/webmozart/puli/css/bootstrap'),
            '/webmozart/puli/css/bootstrap/bootstrap.css' => $repo->get('/webmozart/puli/css/bootstrap/bootstrap.css'),
            '/webmozart/puli/css/fonts.css' => $repo->get('/webmozart/puli/css/fonts.css'),
            '/webmozart/puli/css/reset.css' => $repo->get('/webmozart/puli/css/reset.css'),
            '/webmozart/puli/css/style.css' => $repo->get('/webmozart/puli/css/style.css'),
        );

        $this->assertSame($expected, iterator_to_array($iterator));
    }

    /**
     * @depends testFilterPathPrefix
     */
    public function testCurrentAsResourceExplicit()
    {
        $repo = new ResourceRepository();
        $repo->add('/webmozart/puli', __DIR__.'/Fixtures');

        $iterator = new ResourceFilterIterator(
            new \RecursiveIteratorIterator(
                new DirectoryResourceIterator($repo->get('/')),
                \RecursiveIteratorIterator::SELF_FIRST
            ),
            '/webmozart/puli/css',
            ResourceFilterIterator::MATCH_PREFIX
                | ResourceFilterIterator::CURRENT_AS_RESOURCE
        );

        $expected = array(
            '/webmozart/puli/css' => $repo->get('/webmozart/puli/css'),
            '/webmozart/puli/css/bootstrap' => $repo->get('/webmozart/puli/css/bootstrap'),
            '/webmozart/puli/css/bootstrap/bootstrap.css' => $repo->get('/webmozart/puli/css/bootstrap/bootstrap.css'),
            '/webmozart/puli/css/fonts.css' => $repo->get('/webmozart/puli/css/fonts.css'),
            '/webmozart/puli/css/reset.css' => $repo->get('/webmozart/puli/css/reset.css'),
            '/webmozart/puli/css/style.css' => $repo->get('/webmozart/puli/css/style.css'),
        );

        $this->assertSame($expected, iterator_to_array($iterator));
    }

    /**
     * @depends testFilterPathPrefix
     */
    public function testCurrentAsRealPath()
    {
        $repo = new ResourceRepository();
        $repo->add('/webmozart/puli', __DIR__.'/Fixtures');

        $iterator = new ResourceFilterIterator(
            new \RecursiveIteratorIterator(
                new DirectoryResourceIterator($repo->get('/')),
                \RecursiveIteratorIterator::SELF_FIRST
            ),
            '/webmozart/puli/css',
            ResourceFilterIterator::MATCH_PREFIX
                | ResourceFilterIterator::CURRENT_AS_REAL_PATH
        );

        $expected = array(
            '/webmozart/puli/css' => __DIR__.'/Fixtures/css',
            '/webmozart/puli/css/bootstrap' => __DIR__.'/Fixtures/css/bootstrap',
            '/webmozart/puli/css/bootstrap/bootstrap.css' => __DIR__.'/Fixtures/css/bootstrap/bootstrap.css',
            '/webmozart/puli/css/fonts.css' => __DIR__.'/Fixtures/css/fonts.css',
            '/webmozart/puli/css/reset.css' => __DIR__.'/Fixtures/css/reset.css',
            '/webmozart/puli/css/style.css' => __DIR__.'/Fixtures/css/style.css',
        );

        $this->assertSame($expected, iterator_to_array($iterator));
    }

    /**
     * @depends testFilterPathPrefix
     */
    public function testCurrentAsNamePath()
    {
        $repo = new ResourceRepository();
        $repo->add('/webmozart/puli', __DIR__.'/Fixtures');

        $iterator = new ResourceFilterIterator(
            new \RecursiveIteratorIterator(
                new DirectoryResourceIterator($repo->get('/')),
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
            '/webmozart/puli/css/reset.css' => 'reset.css',
            '/webmozart/puli/css/style.css' => 'style.css',
        );

        $this->assertSame($expected, iterator_to_array($iterator));
    }

    /**
     * @depends testFilterPathPrefix
     */
    public function testKeyAsPath()
    {
        $repo = new ResourceRepository();
        $repo->add('/webmozart/puli', __DIR__.'/Fixtures');

        $iterator = new ResourceFilterIterator(
            new \RecursiveIteratorIterator(
                new DirectoryResourceIterator($repo->get('/')),
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
            '/webmozart/puli/css/reset.css' => '/webmozart/puli/css/reset.css',
            '/webmozart/puli/css/style.css' => '/webmozart/puli/css/style.css',
        );

        $this->assertSame($expected, iterator_to_array($iterator));
    }

    /**
     * @depends testFilterPathPrefix
     */
    public function testKeyAsCursor()
    {
        $repo = new ResourceRepository();
        $repo->add('/webmozart/puli', __DIR__.'/Fixtures');

        $iterator = new ResourceFilterIterator(
            new DirectoryResourceIterator($repo->get('/webmozart/puli/css')),
            '.css',
            ResourceFilterIterator::MATCH_SUFFIX
                | ResourceFilterIterator::CURRENT_AS_PATH
                | ResourceFilterIterator::KEY_AS_CURSOR
        );

        $expected = array(
            0 => '/webmozart/puli/css/fonts.css',
            1 => '/webmozart/puli/css/reset.css',
            2 => '/webmozart/puli/css/style.css',
        );

        $this->assertSame($expected, iterator_to_array($iterator));
    }
}
