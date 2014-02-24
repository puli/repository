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
use Webmozart\Puli\Resource\ResourceCollectionIterator;

/**
 * @since  1.0
 * @author Bernhard Schussek <bschussek@gmail.com>
 */
class ResourceCollectionIteratorTest extends \PHPUnit_Framework_TestCase
{
    public function testDefaultIteration()
    {
        $repo = new ResourceRepository();
        $repo->add('/webmozart/puli', __DIR__.'/Fixtures');

        $iterator = new ResourceCollectionIterator($repo->listDirectory('/'));

        $recursiveIterator = new \RecursiveIteratorIterator(
            $iterator,
            \RecursiveIteratorIterator::SELF_FIRST
        );

        $expected = array(
            '/webmozart' => $repo->get('/webmozart'),
            '/webmozart/puli' => $repo->get('/webmozart/puli'),
            '/webmozart/puli/config' => $repo->get('/webmozart/puli/config'),
            '/webmozart/puli/config/config.yml' => $repo->get('/webmozart/puli/config/config.yml'),
            '/webmozart/puli/css' => $repo->get('/webmozart/puli/css'),
            '/webmozart/puli/css/bootstrap' => $repo->get('/webmozart/puli/css/bootstrap'),
            '/webmozart/puli/css/bootstrap/bootstrap.css' => $repo->get('/webmozart/puli/css/bootstrap/bootstrap.css'),
            '/webmozart/puli/css/fonts.css' => $repo->get('/webmozart/puli/css/fonts.css'),
            '/webmozart/puli/css/reset.css' => $repo->get('/webmozart/puli/css/reset.css'),
            '/webmozart/puli/css/style.css' => $repo->get('/webmozart/puli/css/style.css'),
            '/webmozart/puli/images' => $repo->get('/webmozart/puli/images'),
            '/webmozart/puli/images/bg.png' => $repo->get('/webmozart/puli/images/bg.png'),
            '/webmozart/puli/installer.json' => $repo->get('/webmozart/puli/installer.json'),
        );

        $this->assertSame($expected, iterator_to_array($recursiveIterator));
    }

    public function testCurrentAsPath()
    {
        $repo = new ResourceRepository();
        $repo->add('/webmozart/puli', __DIR__.'/Fixtures');

        $iterator = new ResourceCollectionIterator(
            $repo->listDirectory('/'),
            ResourceCollectionIterator::CURRENT_AS_PATH
        );

        $recursiveIterator = new \RecursiveIteratorIterator(
            $iterator,
            \RecursiveIteratorIterator::SELF_FIRST
        );

        $expected = array(
            '/webmozart' => '/webmozart',
            '/webmozart/puli' => '/webmozart/puli',
            '/webmozart/puli/config' => '/webmozart/puli/config',
            '/webmozart/puli/config/config.yml' => '/webmozart/puli/config/config.yml',
            '/webmozart/puli/css' => '/webmozart/puli/css',
            '/webmozart/puli/css/bootstrap' => '/webmozart/puli/css/bootstrap',
            '/webmozart/puli/css/bootstrap/bootstrap.css' => '/webmozart/puli/css/bootstrap/bootstrap.css',
            '/webmozart/puli/css/fonts.css' => '/webmozart/puli/css/fonts.css',
            '/webmozart/puli/css/reset.css' => '/webmozart/puli/css/reset.css',
            '/webmozart/puli/css/style.css' => '/webmozart/puli/css/style.css',
            '/webmozart/puli/images' => '/webmozart/puli/images',
            '/webmozart/puli/images/bg.png' => '/webmozart/puli/images/bg.png',
            '/webmozart/puli/installer.json' => '/webmozart/puli/installer.json',
        );

        $this->assertSame($expected, iterator_to_array($recursiveIterator));
    }

    public function testCurrentAsRealPath()
    {
        $repo = new ResourceRepository();
        $repo->add('/webmozart/puli', __DIR__.'/Fixtures');

        $iterator = new ResourceCollectionIterator(
            $repo->listDirectory('/'),
            ResourceCollectionIterator::CURRENT_AS_REAL_PATH
        );

        $recursiveIterator = new \RecursiveIteratorIterator(
            $iterator,
            \RecursiveIteratorIterator::SELF_FIRST
        );

        $expected = array(
            '/webmozart' => null,
            '/webmozart/puli' => __DIR__.'/Fixtures',
            '/webmozart/puli/config' => __DIR__.'/Fixtures/config',
            '/webmozart/puli/config/config.yml' => __DIR__.'/Fixtures/config/config.yml',
            '/webmozart/puli/css' => __DIR__.'/Fixtures/css',
            '/webmozart/puli/css/bootstrap' => __DIR__.'/Fixtures/css/bootstrap',
            '/webmozart/puli/css/bootstrap/bootstrap.css' => __DIR__.'/Fixtures/css/bootstrap/bootstrap.css',
            '/webmozart/puli/css/fonts.css' => __DIR__.'/Fixtures/css/fonts.css',
            '/webmozart/puli/css/reset.css' => __DIR__.'/Fixtures/css/reset.css',
            '/webmozart/puli/css/style.css' => __DIR__.'/Fixtures/css/style.css',
            '/webmozart/puli/images' => __DIR__.'/Fixtures/images',
            '/webmozart/puli/images/bg.png' => __DIR__.'/Fixtures/images/bg.png',
            '/webmozart/puli/installer.json' => __DIR__.'/Fixtures/installer.json',
        );

        $this->assertSame($expected, iterator_to_array($recursiveIterator));
    }

    public function testCurrentAsName()
    {
        $repo = new ResourceRepository();
        $repo->add('/webmozart/puli', __DIR__.'/Fixtures');

        $iterator = new ResourceCollectionIterator(
            $repo->listDirectory('/'),
            ResourceCollectionIterator::CURRENT_AS_NAME
        );

        $recursiveIterator = new \RecursiveIteratorIterator(
            $iterator,
            \RecursiveIteratorIterator::SELF_FIRST
        );

        $expected = array(
            '/webmozart' => 'webmozart',
            '/webmozart/puli' => 'puli',
            '/webmozart/puli/config' => 'config',
            '/webmozart/puli/config/config.yml' => 'config.yml',
            '/webmozart/puli/css' => 'css',
            '/webmozart/puli/css/bootstrap' => 'bootstrap',
            '/webmozart/puli/css/bootstrap/bootstrap.css' => 'bootstrap.css',
            '/webmozart/puli/css/fonts.css' => 'fonts.css',
            '/webmozart/puli/css/reset.css' => 'reset.css',
            '/webmozart/puli/css/style.css' => 'style.css',
            '/webmozart/puli/images' => 'images',
            '/webmozart/puli/images/bg.png' => 'bg.png',
            '/webmozart/puli/installer.json' => 'installer.json',
        );

        $this->assertSame($expected, iterator_to_array($recursiveIterator));
    }

    public function testKeyAsCursor()
    {
        $repo = new ResourceRepository();
        $repo->add('/webmozart/puli', __DIR__.'/Fixtures');

        $iterator = new ResourceCollectionIterator(
            $repo->listDirectory('/webmozart/puli'),
            ResourceCollectionIterator::CURRENT_AS_PATH
                | ResourceCollectionIterator::KEY_AS_CURSOR
        );

        $expected = array(
            0 => '/webmozart/puli/config',
            1 => '/webmozart/puli/css',
            2 => '/webmozart/puli/images',
            3 => '/webmozart/puli/installer.json',
        );

        $this->assertSame($expected, iterator_to_array($iterator));
    }
}
