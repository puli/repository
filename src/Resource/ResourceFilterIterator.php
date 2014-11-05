<?php

/*
 * This file is part of the Puli package.
 *
 * (c) Bernhard Schussek <bschussek@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Webmozart\Puli\Resource;

use Iterator;

/**
 * @since  1.0
 * @author Bernhard Schussek <bschussek@gmail.com>
 */
class ResourceFilterIterator extends \FilterIterator
{
    const CURRENT_AS_RESOURCE = 1;

    const CURRENT_AS_PATH = 2;

    const CURRENT_AS_NAME = 4;

    const KEY_AS_PATH = 64;

    /**
     * Use the cursor as key.
     *
     * Attention: Don't use this mode when iterating recursively, as PHP's
     * {@link \RecursiveIteratorIterator} skips inner nodes then.
     */
    const KEY_AS_CURSOR = 128;

    const FILTER_BY_PATH = 512;

    const FILTER_BY_NAME = 1024;

    const MATCH_PREFIX = 8192;

    const MATCH_SUFFIX = 16384;

    const MATCH_REGEX = 32768;

    private $pattern;

    private $patternLength;

    private $mode;

    private $cursor = 0;

    public function __construct(Iterator $iterator, $pattern, $mode = null)
    {
        parent::__construct($iterator);

        if (!($mode & (self::FILTER_BY_PATH | self::FILTER_BY_NAME))) {
            $mode |= self::FILTER_BY_PATH;
        }

        if (!($mode & (self::MATCH_PREFIX | self::MATCH_SUFFIX | self::MATCH_REGEX))) {
            $mode |= self::MATCH_REGEX;
        }

        if (!($mode & (self::CURRENT_AS_RESOURCE | self::CURRENT_AS_PATH | self::CURRENT_AS_NAME))) {
            $mode |= self::CURRENT_AS_RESOURCE;
        }

        if (!($mode & (self::KEY_AS_PATH | self::KEY_AS_CURSOR))) {
            $mode |= self::KEY_AS_PATH;
        }

        $pattern = (string) $pattern;

        if ('' === $pattern) {
            throw new \InvalidArgumentException('The pattern must not be empty.');
        }

        $this->pattern = $pattern;
        $this->patternLength = strlen($pattern);
        $this->mode = $mode;
    }

    public function current()
    {
        if ($this->mode & self::CURRENT_AS_RESOURCE) {
            return parent::current();
        }

        if ($this->mode & self::CURRENT_AS_PATH) {
            return parent::current()->getPath();
        }

        return parent::current()->getName();
    }

    public function key()
    {
        if ($this->mode & self::KEY_AS_PATH) {
            return parent::current()->getPath();
        }

        return $this->cursor;
    }

    public function next()
    {
        parent::next();

        ++$this->cursor;
    }

    public function rewind()
    {
        parent::rewind();

        $this->cursor = 0;
    }

    public function accept()
    {
        if ($this->mode & self::FILTER_BY_PATH) {
            $value = parent::current()->getPath();
        } else {
            $value = parent::current()->getName();
        }

        if ($this->mode & self::MATCH_PREFIX) {
            return 0 === strpos($value, $this->pattern);
        } elseif ($this->mode & self::MATCH_SUFFIX) {
            return $this->pattern === substr($value, -$this->patternLength);
        } else {
            return preg_match($this->pattern, $value);
        }
    }
}
