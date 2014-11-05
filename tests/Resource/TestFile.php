<?php

/*
 * This file is part of the Puli package.
 *
 * (c) Bernhard Schussek <bschussek@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Webmozart\Puli\Tests\Resource;

use Webmozart\Puli\Resource\FileResourceInterface;
use Webmozart\Puli\Resource\ResourceInterface;

/**
 * @since  1.0
 * @author Bernhard Schussek <bschussek@gmail.com>
 */
class TestFile implements FileResourceInterface
{
    private $path;

    private $overrides;

    public function __construct($path)
    {
        $this->path = $path;
    }

    public function getContents()
    {
        return file_get_contents($this->path);
    }

    public function getPath()
    {
        return $this->path;
    }

    public function getName()
    {
        return basename($this->path);
    }

    public function copyTo($path)
    {
        $copy = clone $this;
        $copy->path = $path;

        return $copy;
    }

    public function override(ResourceInterface $resource)
    {
        $copy = clone $this;
        $copy->path = $resource->getPath();
        $copy->overrides = $resource;

        return $copy;
    }
}
