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

/**
 * @since  1.0
 * @author Bernhard Schussek <bschussek@gmail.com>
 */
class DirectoryResourceIteratorTest extends \PHPUnit_Framework_TestCase
{
    public function testDefaultIteration()
    {
        $repo = new ResourceRepository();
        $repo->add('/webmozart/puli', __DIR__.'/Fixtures');

        $iterator = new DirectoryResourceIterator($repo->get('/'));

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

        $iterator = new DirectoryResourceIterator(
            $repo->get('/'),
            DirectoryResourceIterator::CURRENT_AS_PATH
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
}
