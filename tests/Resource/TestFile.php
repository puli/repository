<?php

/*
 * This file is part of the puli/repository package.
 *
 * (c) Bernhard Schussek <bschussek@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Puli\Repository\Tests\Resource;

use Puli\Repository\Resource\AbstractResource;
use Puli\Repository\Resource\FileResource;
use Puli\Repository\Resource\Resource;

/**
 * @since  1.0
 * @author Bernhard Schussek <bschussek@gmail.com>
 */
class TestFile extends AbstractResource implements FileResource
{
    const CONTENTS = "LINE 1\nLINE 2\n";

    private $contents;

    public function __construct($path = null, $contents = self::CONTENTS)
    {
        parent::__construct($path);

        $this->contents = $contents;
    }

    public function getContents()
    {
        return $this->contents;
    }

    public function getSize()
    {
        return strlen($this->contents);
    }

    public function getLastAccessedAt()
    {
        return 0;
    }

    public function getLastModifiedAt()
    {
        return 0;
    }
}
