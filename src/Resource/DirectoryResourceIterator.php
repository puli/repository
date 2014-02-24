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

use RecursiveIterator;

/**
 * @since  1.0
 * @author Bernhard Schussek <bschussek@gmail.com>
 */
class DirectoryResourceIterator implements \RecursiveIterator
{
    const CURRENT_AS_RESOURCE = 1;

    const CURRENT_AS_PATH = 2;

    const CURRENT_AS_REAL_PATH = 4;

    const CURRENT_AS_NAME = 8;

    const KEY_AS_PATH = 64;

    private $directory;

    /**
     * @var ResourceInterface[]
     */
    private $resources;

    private $cursor = 0;

    private $mode;

    public function __construct(DirectoryResourceInterface $directory, $mode = null)
    {
        if (!($mode & (self::CURRENT_AS_PATH | self::CURRENT_AS_RESOURCE | self::CURRENT_AS_REAL_PATH | self::CURRENT_AS_NAME))) {
            $mode |= self::CURRENT_AS_RESOURCE;
        }

        if (!($mode & (self::KEY_AS_PATH))) {
            $mode |= self::KEY_AS_PATH;
        }

        $this->directory = $directory;
        $this->resources = $directory->all()->toArray();
        $this->mode = $mode;
    }

    public function current()
    {
        if ($this->mode & self::CURRENT_AS_RESOURCE) {
            return $this->resources[$this->cursor];
        }

        if ($this->mode & self::CURRENT_AS_PATH) {
            return $this->resources[$this->cursor]->getPath();
        }

        if ($this->mode & self::CURRENT_AS_REAL_PATH) {
            return $this->resources[$this->cursor]->getRealPath();
        }

        return $this->resources[$this->cursor]->getName();
    }

    public function next()
    {
        ++$this->cursor;
    }

    public function key()
    {
        if ($this->mode & self::KEY_AS_PATH) {
            return $this->resources[$this->cursor]->getPath();
        }

        return $this->cursor;
    }

    public function valid()
    {
        return $this->cursor < count($this->resources);
    }

    public function rewind()
    {
        $this->cursor = 0;
    }

    public function hasChildren()
    {
        return $this->resources[$this->cursor] instanceof DirectoryResourceInterface;
    }

    public function getChildren()
    {
        return new static($this->resources[$this->cursor], $this->mode);
    }
}
