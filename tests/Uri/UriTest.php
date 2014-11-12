<?php

/*
 * This file is part of the Puli package.
 *
 * (c) Bernhard Schussek <bschussek@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Webmozart\Puli\Tests\Uri;

use Webmozart\Puli\Uri\Uri;

/**
 * @since  1.0
 * @author Bernhard Schussek <bschussek@gmail.com>
 */
class UriTest extends \PHPUnit_Framework_TestCase
{

    public function provideValidUris()
    {
        return array(
            array('scheme:///path/to/resource', array(
                'scheme' => 'scheme',
                'path' => '/path/to/resource',
            )),
            array('psr4:///path/to/resource', array(
                'scheme' => 'psr4',
                'path' => '/path/to/resource',
            )),
            array('/path/to/resource', array(
                'scheme' => '',
                'path' => '/path/to/resource',
            )),
        );
    }

    /**
     * @dataProvider provideValidUris
     */
    public function testParse($uri, $parts)
    {
        $this->assertEquals($parts, Uri::parse($uri));
    }

    public function provideInvalidUris()
    {
        return array(
            array(''),
            array(null),
            array(123),
            array(new \stdClass()),
            array(':///path/to/resource'),
            array('1foo:///path/to/resource'),
            array('foo@:///path/to/resource'),
            array('scheme:/path/to/resource'),
            array('scheme//path/to/resource'),
            array('scheme:://path/to/resource'),
        );
    }

    /**
     * @dataProvider provideInvalidUris
     * @expectedException \Webmozart\Puli\Uri\InvalidUriException
     */
    public function testParseInvalid($uri)
    {
        Uri::parse($uri);
    }
}
