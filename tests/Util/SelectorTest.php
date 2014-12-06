<?php

/*
 * This file is part of the Puli package.
 *
 * (c) Bernhard Schussek <bschussek@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Puli\Repository\Tests\Util;

use Puli\Repository\Util\Selector;

/**
 * @since  1.0
 * @author Bernhard Schussek <bschussek@gmail.com>
 */
class SelectorTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @dataProvider provideMatches
     */
    public function testToRegEx($path, $isMatch)
    {
        $regExp = Selector::toRegEx('/foo/*.js~');

        $this->assertSame($isMatch, preg_match($regExp, $path));
    }

    public function provideMatches()
    {
        return array(
            // The method assumes that the path is already consolidated
            array('/bar/baz.js~', 0),
            array('/foo/baz.js~', 1),
            array('/foo/../bar/baz.js~', 1),
            array('/foo/../foo/baz.js~', 1),
            array('/bar/baz.js', 0),
            array('/foo/bar/baz.js~', 1),
            array('foo/baz.js~', 0),
            array('/bar/foo/baz.js~', 0),
            array('/bar/.js~', 0),
        );
    }

    /**
     * @dataProvider provideStaticPrefixes
     */
    public function testGetStaticPrefix($selector, $prefix)
    {
        $this->assertSame($prefix, Selector::getStaticPrefix($selector));
    }

    public function provideStaticPrefixes()
    {
        return array(
            // The method assumes that the path is already consolidated
            array('/foo/baz/../*/bar/*', '/foo/baz/../'),
            array('/foo/baz/bar*', '/foo/baz/bar'),
        );
    }

    /**
     * @dataProvider provideBasePaths
     */
    public function testGetBasePath($selector, $basePath)
    {
        $this->assertSame($basePath, Selector::getBasePath($selector));
    }

    public function provideBasePaths()
    {
        return array(
            // The method assumes that the path is already consolidated
            array('/foo/baz/../*/bar/*', '/foo/baz/..'),
            array('/foo/baz/bar*', '/foo/baz'),
            array('/foo/baz/bar', '/foo/baz'),
            array('/foo/baz*', '/foo'),
            array('/foo*', '/'),
            array('/*', '/'),
            array('foo*/baz/bar', ''),
            array('foo*', ''),
            array('*', ''),
        );
    }
}
