<?php

/*
 * This file is part of the puli/repository package.
 *
 * (c) Bernhard Schussek <bschussek@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Puli\Repository\Assert;

/**
 * @since  1.0
 * @author Bernhard Schussek <bschussek@gmail.com>
 */
class Assertion extends \Assert\Assertion
{
    public static function path($path)
    {
        Assertion::string($path, 'The path must be a string. Got: %2$s');
        Assertion::notEmpty($path, 'The path must not be empty.');
        Assertion::startsWith($path, '/', 'The path %s is not absolute.');
    }

    public static function selector($selector)
    {
        Assertion::string($selector, 'The selector must be a string. Got: %2$s');
        Assertion::notEmpty($selector, 'The selector must not be empty.');
        Assertion::startsWith($selector, '/', 'The selector %s is not absolute.');
    }

    private function __construct()
    {
    }
}
