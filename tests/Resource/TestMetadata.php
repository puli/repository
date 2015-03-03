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

use Puli\Repository\Api\Resource\ResourceMetadata;

/**
 * @since  1.0
 * @author Bernhard Schussek <bschussek@gmail.com>
 */
class TestMetadata extends ResourceMetadata
{
    /**
     * @var int
     */
    private $creationTime = 0;

    /**
     * @var int
     */
    private $accessTime = 0;

    /**
     * @var int
     */
    private $modificationTime = 0;

    /**
     * @var int
     */
    private $size = 0;

    /**
     * @return int
     */
    public function getCreationTime()
    {
        return $this->creationTime;
    }

    /**
     * @param int $creationTime
     */
    public function setCreationTime($creationTime)
    {
        $this->creationTime = $creationTime;
    }

    /**
     * @return int
     */
    public function getAccessTime()
    {
        return $this->accessTime;
    }

    /**
     * @param int $accessTime
     */
    public function setAccessTime($accessTime)
    {
        $this->accessTime = $accessTime;
    }

    /**
     * @return int
     */
    public function getModificationTime()
    {
        return $this->modificationTime;
    }

    /**
     * @param int $modificationTime
     */
    public function setModificationTime($modificationTime)
    {
        $this->modificationTime = $modificationTime;
    }

    /**
     * @return int
     */
    public function getSize()
    {
        return $this->size;
    }

    /**
     * @param int $size
     */
    public function setSize($size)
    {
        $this->size = $size;
    }
}
