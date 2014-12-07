<?php

/*
 * This file is part of the puli/repository package.
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

    // From the PHP manual: To specify a literal single quote, escape it with a
    // backslash (\). To specify a literal backslash, double it (\\).
    // All other instances of backslash will be treated as a literal backslash

    public function testEscapedWildcard()
    {
        // evaluates to "\*"
        $regExp = Selector::toRegEx('/foo/\\*.js~');

        $this->assertSame(0, preg_match($regExp, '/foo/baz.js~'));
    }

    public function testEscapedWildcard2()
    {
        // evaluates to "\*"
        $regExp = Selector::toRegEx('/foo/\*.js~');

        $this->assertSame(0, preg_match($regExp, '/foo/baz.js~'));
    }

    public function testMatchEscapedWildcard()
    {
        // evaluates to "\*"
        $regExp = Selector::toRegEx('/foo/\\*.js~');

        $this->assertSame(1, preg_match($regExp, '/foo/*.js~'));
    }

    public function testMatchWildcardWithLeadingBackslash()
    {
        // evaluates to "\\*"
        $regExp = Selector::toRegEx('/foo/\\\\*.js~');

        $this->assertSame(1, preg_match($regExp, '/foo/\\baz.js~'));
        $this->assertSame(1, preg_match($regExp, '/foo/\baz.js~'));
        $this->assertSame(0, preg_match($regExp, '/foo/baz.js~'));
    }

    public function testMatchWildcardWithLeadingBackslash2()
    {
        // evaluates to "\\*"
        $regExp = Selector::toRegEx('/foo/\\\*.js~');

        $this->assertSame(1, preg_match($regExp, '/foo/\\baz.js~'));
        $this->assertSame(1, preg_match($regExp, '/foo/\baz.js~'));
        $this->assertSame(0, preg_match($regExp, '/foo/baz.js~'));
    }

    public function testMatchEscapedWildcardWithLeadingBackslash()
    {
        // evaluates to "\\\*"
        $regExp = Selector::toRegEx('/foo/\\\\\\*.js~');

        $this->assertSame(1, preg_match($regExp, '/foo/\\*.js~'));
        $this->assertSame(1, preg_match($regExp, '/foo/\*.js~'));
        $this->assertSame(0, preg_match($regExp, '/foo/*.js~'));
        $this->assertSame(0, preg_match($regExp, '/foo/\\baz.js~'));
        $this->assertSame(0, preg_match($regExp, '/foo/\baz.js~'));
    }

    public function testMatchWildcardWithTwoLeadingBackslashes()
    {
        // evaluates to "\\\\*"
        $regExp = Selector::toRegEx('/foo/\\\\\\\\*.js~');

        $this->assertSame(1, preg_match($regExp, '/foo/\\\\baz.js~'));
        $this->assertSame(1, preg_match($regExp, '/foo/\\\baz.js~'));
        $this->assertSame(0, preg_match($regExp, '/foo/\\baz.js~'));
        $this->assertSame(0, preg_match($regExp, '/foo/\baz.js~'));
        $this->assertSame(0, preg_match($regExp, '/foo/baz.js~'));
    }

    public function testMatchEscapedWildcardWithTwoLeadingBackslashes()
    {
        // evaluates to "\\\\*"
        $regExp = Selector::toRegEx('/foo/\\\\\\\\\\*.js~');

        $this->assertSame(1, preg_match($regExp, '/foo/\\\\*.js~'));
        $this->assertSame(1, preg_match($regExp, '/foo/\\\*.js~'));
        $this->assertSame(0, preg_match($regExp, '/foo/\\*.js~'));
        $this->assertSame(0, preg_match($regExp, '/foo/\*.js~'));
        $this->assertSame(0, preg_match($regExp, '/foo/*.js~'));
        $this->assertSame(0, preg_match($regExp, '/foo/\\\\baz.js~'));
        $this->assertSame(0, preg_match($regExp, '/foo/\\\baz.js~'));
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
