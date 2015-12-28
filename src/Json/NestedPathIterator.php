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
use Traversable;

/**
 * @since  1.0
 *
 * @author Bernhard Schussek <bschussek@gmail.com>
 */
class NestedPathIterator extends FilterIterator
{
    private $subDirectory;

    public function __construct(Iterator $iterator, $nestedPath)
    {
        parent::__construct($iterator);

        $this->subDirectory = '/'.$nestedPath;
    }

    public function current()
    {
        return rtrim($this->getInnerIterator()->current(), '/').$this->subDirectory;
    }

    public function key()
    {
        return rtrim($this->getInnerIterator()->key(), '/').$this->subDirectory;
    }

    public function accept()
    {
        return file_exists($this->current());
    }
}
