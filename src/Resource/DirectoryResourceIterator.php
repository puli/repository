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
class DirectoryResourceIterator extends ResourceCollectionIterator
{
    public function __construct(DirectoryResourceInterface $directory, $mode = null)
    {
        parent::__construct($directory->all(), $mode);
    }

    public function getChildren()
    {
        return new static($this->resources[$this->cursor], $this->mode);
    }
}
