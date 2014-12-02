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
        $regExp = Selector::toRegEx('/*/*.js~');

        $this->assertSame($isMatch, preg_match($regExp, $path));
    }

    public function provideMatches()
    {
        return array(
            array('/bar/baz.js~', 1),
            array('/foo/baz.js~', 1),
            array('/foo/../bar/baz.js~', 0),
            array('/foo/../foo/baz.js~', 0),
            array('/bar/baz.js', 0),
            array('/foo/bar/baz.js~', 0),
            array('foo/baz.js~', 0),
            array('/bar/foo/baz.js~', 0),
            array('/bar/.js~', 0),
        );
    }

    /**
     * @dataProvider provideGlobs
     */
    public function testToGlob($input, $output)
    {
        $this->assertSame($output, Selector::toGlob($input));
    }

    public function provideGlobs()
    {
        return array(
            array('/path/to/file*', '/path/to/file*'),
            array('/path/to/*', '/path/to/{.,}*'),
            array('/path/to/{.,}*', '/path/to/{.,}*'),
            array('/path/to/{a,b,c}*', '/path/to/{a,b,c}*'),
        );
    }

    public function testGetStaticPrefix()
    {
        // The method assumes that the path is already consolidated
        $this->assertSame('/foo/baz/../', Selector::getStaticPrefix('/foo/baz/../*/bar/*'));
    }
}
