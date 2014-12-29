<?php

/*
 * This file is part of the puli/repository package.
 *
 * (c) Bernhard Schussek <bschussek@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Puli\Repository\Iterator;

use Iterator;
use Puli\Repository\Glob\Glob;

/**
 * Filters an iterator by a glob.
 *
 * @since  1.0
 * @author Bernhard Schussek <bschussek@gmail.com>
 * @see    Glob
 */
class GlobFilterIterator extends RegexFilterIterator
{
    /**
     * Creates a new iterator.
     *
     * @param string   $glob          The canonical glob.
     * @param Iterator $innerIterator The filtered iterator.
     */
    public function __construct($glob, Iterator $innerIterator)
    {
        parent::__construct(
            Glob::toRegEx($glob),
            Glob::getStaticPrefix($glob),
            $innerIterator
        );
    }
}
