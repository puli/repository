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
use Assert\Assertion;
use RecursiveIterator;

/**
 * Recursive directory iterator with a working seek() method.
 *
 * See https://bugs.php.net/bug.php?id=68557
 *
 * Contrary to the native RecursiveDirectoryIterator, this iterator also returns
 * the directory entries sorted alphabetically. Hence further sorting is not
 * necessary.
 *
 * @since  1.0
 * @author Bernhard Schussek <bschussek@gmail.com>
 */
class RecursiveDirectoryIterator extends ArrayIterator implements RecursiveIterator
{
    /**
     * Flag: Return current value as file path.
     */
    const CURRENT_AS_PATH = 1;

    /**
     * Flag: Return current value as file name.
     */
    const CURRENT_AS_FILE = 2;

    /**
     * @var int
     */
    private $flags;

    /**
     * Creates an iterator for the given path.
     *
     * @param string $path  A canonical directory path.
     * @param int    $flags The flags.
     */
    public function __construct($path, $flags = null)
    {
        Assertion::directory($path);

        if (!($flags & (self::CURRENT_AS_FILE | self::CURRENT_AS_PATH))) {
            $flags |= self::CURRENT_AS_PATH;
        }

        $basePath = rtrim($path, '/').'/';
        $paths = array();

        foreach (scandir($path) as $file) {
            if ('.' === $file || '..' === $file) {
                continue;
            }

            $paths[$basePath.$file] = ($flags & self::CURRENT_AS_FILE)
                ? $file
                : $basePath.$file;
        }

        parent::__construct($paths);

        $this->flags = $flags;
    }

    public function hasChildren()
    {
        return is_dir($this->key());
    }

    public function getChildren()
    {
        return new static($this->key(), $this->flags);
    }
}
