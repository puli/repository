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

use ArrayIterator;
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
class GlobIterator extends SelectorIterator
{
    /**
     * Creates a new iterator.
     *
     * @param string $selector The glob pattern.
     */
    public function __construct($selector)
    {
        $basePath = Selector::getBasePath($selector);

        if (file_exists($selector)) {
            // If the glob is a file path, return that path
            $innerIterator = new ArrayIterator(array($selector => $selector));
        } elseif (is_dir($basePath)) {
            // Otherwise scan the glob's base directory for matches
            $innerIterator = new RecursiveIteratorIterator(
                new RecursiveDirectoryIterator($basePath),
                RecursiveIteratorIterator::SELF_FIRST
            );
        } else {
            // If the glob's base directory does not exist, return nothing
            $innerIterator = new EmptyIterator();
        }

        parent::__construct($selector, $innerIterator);
    }
}
