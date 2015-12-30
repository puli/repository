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
use Puli\Repository\Json\ReferenceIterator;
use RecursiveIterator;
use Traversable;

/**
 * @since  1.0
 *
 * @author Bernhard Schussek <bschussek@gmail.com>
 */
class FollowLinksInnerIterator extends IteratorIterator implements RecursiveIterator
{
    private $json;

    private $baseDirectory;

    public function __construct(Traversable $iterator, array &$json, $baseDirectory)
    {
        parent::__construct($iterator);

        $this->json = &$json;
        $this->baseDirectory = $baseDirectory;
    }

    public function hasChildren()
    {
        $current = $this->current();

        return isset($current{0}) && '@' === $current{0};
    }

    public function getChildren()
    {
        return new FollowLinksInnerIterator(
            new ReferenceIterator(
                $this->json,
                substr($this->current(), 1),
                $this->baseDirectory
            ),
            $this->json,
            $this->baseDirectory
        );
    }
}
