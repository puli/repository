<?php

/*
 * This file is part of the puli/repository package.
 *
 * (c) Bernhard Schussek <bschussek@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Puli\Repository\Json\Util;

use IteratorIterator;
use RecursiveIterator;
use Traversable;

/**
 * @since  1.0
 *
 * @author Bernhard Schussek <bschussek@gmail.com>
 */
class ListDirectoriesInnerIterator extends IteratorIterator implements RecursiveIterator
{
    private $maxDepth;

    public function __construct(Traversable $iterator, $maxDepth = 0)
    {
        parent::__construct($iterator);

        $this->maxDepth = $maxDepth;
    }

    public function hasChildren()
    {
        if (!is_dir($this->current())) {
            return false;
        }

        if (0 === $this->maxDepth) {
            return true;
        }

        return substr_count($this->key(), '/') <= $this->maxDepth;
    }

    public function getChildren()
    {
        return new ListDirectoryIterator($this->key(), $this->current());
    }
}
