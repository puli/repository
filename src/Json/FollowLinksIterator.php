<?php

/*
 * This file is part of the puli/repository package.
 *
 * (c) Bernhard Schussek <bschussek@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Puli\Repository\Json;

use Puli\Repository\Json\Util\FollowLinksInnerIterator;
use RecursiveIteratorIterator;
use Traversable;

/**
 * @since  1.0
 *
 * @author Bernhard Schussek <bschussek@gmail.com>
 */
class FollowLinksIterator extends RecursiveIteratorIterator
{
    public function __construct(Traversable $iterator, array &$json, $baseDirectory)
    {
        parent::__construct(
            new FollowLinksInnerIterator($iterator, $json, $baseDirectory),
            self::SELF_FIRST
        );
    }
}
