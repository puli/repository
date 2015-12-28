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

use FilterIterator;
use Iterator;

/**
 * @since  1.0
 *
 * @author Bernhard Schussek <bschussek@gmail.com>
 */
class FilterPathIterator extends FilterIterator
{
    private $searchPath;

    public function __construct(Iterator $iterator, $searchPath)
    {
        parent::__construct($iterator);

        $this->searchPath = $searchPath;
    }

    public function accept()
    {
        return $this->searchPath === $this->key();
    }
}
