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

    const CURRENT_AS_NAME = 4;

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

    protected $mode;

    public function __construct(ResourceCollectionInterface $resources, $mode = null)
    {
        if (!($mode & (self::CURRENT_AS_PATH | self::CURRENT_AS_RESOURCE | self::CURRENT_AS_NAME))) {
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
            return current($this->resources);
        }

        if ($this->mode & self::CURRENT_AS_PATH) {
            return current($this->resources)->getPath();
        }

        return current($this->resources)->getName();
    }

    public function next()
    {
        next($this->resources);
    }

    public function key()
    {
        if (null === ($key = key($this->resources))) {
            return null;
        }

        if ($this->mode & self::KEY_AS_PATH) {
            return $this->resources[$key]->getPath();
        }

        return $key;
    }

    public function valid()
    {
        return null !== key($this->resources);
    }

    public function rewind()
    {
        reset($this->resources);
    }

    public function hasChildren()
    {
        return current($this->resources) instanceof DirectoryResourceInterface;
    }

    public function getChildren()
    {
        return new static(current($this->resources)->listEntries(), $this->mode);
    }

    public function getCurrentResource()
    {
        return current($this->resources);
    }
}
