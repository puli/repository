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
            '/webmozart/puli/dir' => $repo->get('/webmozart/puli/dir'),
            '/webmozart/puli/dir/nested' => $repo->get('/webmozart/puli/dir/nested'),
            '/webmozart/puli/dir/nested/bar' => $repo->get('/webmozart/puli/dir/nested/bar'),
            '/webmozart/puli/foo' => $repo->get('/webmozart/puli/foo'),
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
            '/webmozart/puli/dir' => '/webmozart/puli/dir',
            '/webmozart/puli/dir/nested' => '/webmozart/puli/dir/nested',
            '/webmozart/puli/dir/nested/bar' => '/webmozart/puli/dir/nested/bar',
            '/webmozart/puli/foo' => '/webmozart/puli/foo',
        );

        $this->assertSame($expected, iterator_to_array($recursiveIterator));
    }
}
