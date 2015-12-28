<?php

/*
 * This file is part of the puli/repository package.
 *
 * (c) Bernhard Schussek <bschussek@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Puli\Repository\Json\Util;

use Webmozart\Glob\Iterator\RecursiveDirectoryIterator;

/**
 * @since  1.0
 *
 * @author Bernhard Schussek <bschussek@gmail.com>
 */
class ListDirectoryIterator extends RecursiveDirectoryIterator
{
    private $path;

    private $basePathLength;

    public function __construct($path, $filesystemPath)
    {
        $this->path = rtrim($path, '/');
        $this->basePathLength = strlen($filesystemPath);

        parent::__construct($filesystemPath, self::CURRENT_AS_PATHNAME | self::KEY_AS_PATHNAME | self::SKIP_DOTS);
    }

    public function key()
    {
        // Generate matching Puli path
        return substr_replace(parent::key(), $this->path, 0, $this->basePathLength);
    }

    public function getChildren()
    {
        return new static($this->key(), $this->current());
    }
}
