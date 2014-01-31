<?php

/*
 * This file is part of the Puli package.
 *
 * (c) Bernhard Schussek <bschussek@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Webmozart\Puli\StreamWrapper;

use Webmozart\Puli\Repository\ResourceRepositoryInterface;

/**
 * @author Bernhard Schussek <bschussek@gmail.com>
 */
class ResourceStreamWrapper
{
    public static function register(ResourceRepositoryInterface $repository)
    {
    }

    private function __construct()
    {
    }
}
