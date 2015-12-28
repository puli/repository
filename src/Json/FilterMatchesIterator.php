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

use Iterator;
use RegexIterator;

/**
 * @since  1.0
 *
 * @author Bernhard Schussek <bschussek@gmail.com>
 */
class FilterMatchesIterator extends RegexIterator
{
    public function __construct(Iterator $iterator, $regex)
    {
        parent::__construct($iterator, $regex, self::MATCH, self::USE_KEY);
    }
}
