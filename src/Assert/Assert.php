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

use Assert\Assertion;

/**
 * Contains domain-specific assertions.
 *
 * @since  1.0
 * @author Bernhard Schussek <bschussek@gmail.com>
 */
class Assert extends Assertion
{
    public static function path($path)
    {
        Assert::string($path, 'The path must be a string. Got: %2$s');
        Assert::notEmpty($path, 'The path must not be empty.');
        Assert::startsWith($path, '/', 'The path %s is not absolute.');
    }

    public static function glob($glob)
    {
        Assert::string($glob, 'The glob must be a string. Got: %2$s');
        Assert::notEmpty($glob, 'The glob must not be empty.');
        Assert::startsWith($glob, '/', 'The glob %s is not absolute.');
    }

    private function __construct()
    {
    }
}
