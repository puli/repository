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

    public static function glob($glob)
    {
        Assertion::string($glob, 'The glob must be a string. Got: %2$s');
        Assertion::notEmpty($glob, 'The glob must not be empty.');
        Assertion::startsWith($glob, '/', 'The glob %s is not absolute.');
    }

    private function __construct()
    {
    }
}
