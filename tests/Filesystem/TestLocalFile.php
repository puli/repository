<?php

/*
 * This file is part of the puli/repository package.
 *
 * (c) Bernhard Schussek <bschussek@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Puli\Repository\Tests\Filesystem;

use Puli\Repository\Filesystem\Resource\LocalResource;
use Puli\Repository\Tests\Resource\TestFile;

/**
 * @since  1.0
 * @author Bernhard Schussek <bschussek@gmail.com>
 */
class TestLocalFile extends TestFile implements LocalResource
{
    private $localPath;

    public function __construct($path, $localPath)
    {
        parent::__construct($path);

        $this->localPath = $localPath;
    }

    /**
     * @return string
     */
    public function getLocalPath()
    {
        return $this->localPath;
    }

    /**
     * @return string[]
     */
    public function getAllLocalPaths()
    {
        return array($this->localPath);
    }

    public function getContents()
    {
        return file_get_contents($this->localPath);
    }

    public function getLastModifiedAt()
    {
        return filemtime($this->localPath);
    }
}
