<?php

/*
 * This file is part of the Puli package.
 *
 * (c) Bernhard Schussek <bschussek@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Webmozart\tests\Pattern;

use Webmozart\Puli\Pattern\GlobPattern;

/**
 * @since  %%NextVersion%%
 * @author Bernhard Schussek <bschussek@gmail.com>
 */
class GlobPatternTest extends \PHPUnit_Framework_TestCase
{
    public function testGetRegularExpression()
    {
        $pattern = new GlobPattern('/foo/../*/*.js~');

        $this->assertSame('~/foo/\.\./[^/]*/[^/]*\.js\~~', $pattern->getRegularExpression());
    }

    public function testGetStaticPrefix()
    {
        $pattern = new GlobPattern('/foo/*/bar/*');

        $this->assertSame('/foo/', $pattern->getStaticPrefix());
    }

    public function testToString()
    {
        $pattern = new GlobPattern('/foo/../*/*.js~');

        $this->assertSame('/foo/../*/*.js~', $pattern->__toString());
    }
}
