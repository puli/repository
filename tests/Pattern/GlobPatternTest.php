<?php

/*
 * This file is part of the Puli package.
 *
 * (c) Bernhard Schussek <bschussek@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Webmozart\Puli\Tests\Pattern;

use Webmozart\Puli\Pattern\GlobPattern;

/**
 * @since  1.0
 * @author Bernhard Schussek <bschussek@gmail.com>
 */
class GlobPatternTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @dataProvider provideMatches
     */
    public function testMatchRegularExpression($path, $isMatch)
    {
        $pattern = new GlobPattern('/foo/../*/*.js~');
        $regExp = $pattern->getRegularExpression();

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

    public function testGetStaticPrefix()
    {
        $pattern = new GlobPattern('/foo/baz/../*/bar/*');

        $this->assertSame('/foo/', $pattern->getStaticPrefix());
    }

    public function testToString()
    {
        $pattern = new GlobPattern('/foo/../*/*.js~');

        $this->assertSame('/*/*.js~', $pattern->__toString());
    }
}
