<?php

/*
 * This file is part of the puli/repository package.
 *
 * (c) Bernhard Schussek <bschussek@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Puli\Repository\Filesystem\Iterator;

use EmptyIterator;
use FilterIterator;
use Puli\Repository\Selector\Selector;
use RecursiveIteratorIterator;
use Webmozart\PathUtil\Path;

/**
 * Implements a Git-like variant of glob.
 *
 * Contrary to {@link glob()}, wildcards "*" also match directory separators
 * in this implementation.
 *
 * @since  1.0
 * @author Bernhard Schussek <bschussek@gmail.com>
 */
class GlobIterator extends FilterIterator
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
     * @param string $glob The glob pattern.
     */
    public function __construct($glob)
    {
        // Selector expects canonical patterns
        $glob = Path::canonicalize($glob);
        $basePath = Selector::getBasePath($glob);

        if (is_dir($basePath)) {
            $innerIterator = new RecursiveIteratorIterator(
                new RecursiveDirectoryIterator($basePath),
                RecursiveIteratorIterator::SELF_FIRST
            );
        } else {
            $innerIterator = new EmptyIterator();
        }

        parent::__construct($innerIterator);

        $this->regExp = Selector::toRegEx($glob);
        $this->staticPrefix = Selector::getStaticPrefix($glob);
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
        $path = $this->current();

        if (0 !== strpos($path, $this->staticPrefix)) {
            return false;
        }

        return (bool) preg_match($this->regExp, $path);
    }
}
