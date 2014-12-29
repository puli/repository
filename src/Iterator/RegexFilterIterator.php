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

use FilterIterator;
use Iterator;
use Puli\Repository\Glob\Glob;

/**
 * Filters an iterator by a regular expression.
 *
 * @since  1.0
 * @author Bernhard Schussek <bschussek@gmail.com>
 * @see    Glob
 */
class RegexFilterIterator extends FilterIterator
{
    /**
     * @var string
     */
    private $regExp;

    /**
     * @var string
     */
    private $staticPrefix;

    /**
     * @var int
     */
    private $cursor = 0;

    /**
     * Creates a new iterator.
     *
     * @param string   $regExp        The regular expression to filter by.
     * @param string   $staticPrefix  The static prefix of the regular
     *                                expression.
     * @param Iterator $innerIterator The filtered iterator.
     */
    public function __construct($regExp, $staticPrefix, Iterator $innerIterator)
    {
        parent::__construct($innerIterator);

        $this->regExp = $regExp;
        $this->staticPrefix = $staticPrefix;
    }

    /**
     * Returns the current position.
     *
     * @return int The current position.
     */
    public function key()
    {
        return $this->cursor;
    }

    /**
     * Advances to the next match.
     *
     * @see Iterator::next()
     */
    public function next()
    {
        parent::next();

        ++$this->cursor;
    }

    /**
     * Accepts paths matching the glob.
     *
     * @return bool Whether the path is accepted.
     */
    public function accept()
    {
        $path = parent::key();

        if (0 !== strpos($path, $this->staticPrefix)) {
            return false;
        }

        return (bool) preg_match($this->regExp, $path);
    }
}
