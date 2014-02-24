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

/**
 * @since  1.0
 * @author Bernhard Schussek <bschussek@gmail.com>
 */
class ResourceCollectionIterator implements \RecursiveIterator
{
    const CURRENT_AS_RESOURCE = 1;

    const CURRENT_AS_PATH = 2;

    const CURRENT_AS_REAL_PATH = 4;

    const CURRENT_AS_NAME = 8;

    const KEY_AS_PATH = 64;

    /**
     * Use the cursor as key.
     *
     * Attention: Don't use this mode when iterating recursively, as PHP's
     * {@link \RecursiveIteratorIterator} skips inner nodes then.
     */
    const KEY_AS_CURSOR = 128;

    /**
     * @var ResourceInterface[]
     */
    protected $resources;

    protected $cursor = 0;

    protected $mode;

    public function __construct(ResourceCollectionInterface $resources, $mode = null)
    {
        if (!($mode & (self::CURRENT_AS_PATH | self::CURRENT_AS_RESOURCE | self::CURRENT_AS_REAL_PATH | self::CURRENT_AS_NAME))) {
            $mode |= self::CURRENT_AS_RESOURCE;
        }

        if (!($mode & (self::KEY_AS_PATH | self::KEY_AS_CURSOR))) {
            $mode |= self::KEY_AS_PATH;
        }

        $this->resources = $resources->toArray();
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
        return new static($this->resources[$this->cursor]->all(), $this->mode);
    }
}
