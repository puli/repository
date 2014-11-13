<?php

/*
 * This file is part of the Puli package.
 *
 * (c) Bernhard Schussek <bschussek@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Puli\Tests\Resource;

use Puli\Resource\AttachableResourceInterface;
use Puli\Resource\FileResourceInterface;
use Puli\Resource\ResourceInterface;
use Puli\ResourceRepositoryInterface;

/**
 * @since  1.0
 * @author Bernhard Schussek <bschussek@gmail.com>
 */
class TestFile implements FileResourceInterface, AttachableResourceInterface
{
    const CONTENTS = "LINE 1\nLINE 2\n";

    private $path;

    private $repo;

    private $overrides;

    public function __construct($path = null)
    {
        $this->path = $path;
    }

    public function getContents()
    {
        return self::CONTENTS;
    }

    public function getSize()
    {
        return strlen(self::CONTENTS);
    }

    public function getLastAccessedAt()
    {
        return 0;
    }

    public function getLastModifiedAt()
    {
        return 0;
    }

    public function getPath()
    {
        return $this->path;
    }

    public function getName()
    {
        return basename($this->path);
    }

    public function attachTo(ResourceRepositoryInterface $repo, $path)
    {
        $this->path = $path;
        $this->repo = $repo;
    }

    public function detach()
    {
        $this->path = null;
        $this->repo = null;
    }

    public function override(ResourceInterface $resource)
    {
        $this->overrides = $resource;
    }

    public function getAttachedRepository()
    {
        return $this->repo;
    }

    public function getOverriddenResource()
    {
        return $this->overrides;
    }
}
