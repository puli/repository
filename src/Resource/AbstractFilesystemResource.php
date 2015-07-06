<?php

/*
 * This file is part of the puli/repository package.
 *
 * (c) Bernhard Schussek <bschussek@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Puli\Repository\Resource;

use Puli\Repository\Api\Resource\FilesystemResource;
use Puli\Repository\Resource\Metadata\FilesystemMetadata;

/**
 * Base class for filesystem resources.
 *
 * @since  1.0
 * @author Bernhard Schussek <bschussek@gmail.com>
 */
abstract class AbstractFilesystemResource extends GenericResource implements FilesystemResource
{
    /**
     * @var string
     */
    private $filesystemPath;

    /**
     * Creates a new filesystem resource.
     *
     * @param string      $filesystemPath The path on the file system.
     * @param string|null $path           The repository path of the resource.
     */
    public function __construct($filesystemPath, $path = null)
    {
        parent::__construct($path);

        $this->filesystemPath = str_replace(DIRECTORY_SEPARATOR, '/', $filesystemPath);
    }

    /**
     * {@inheritdoc}
     */
    public function getFilesystemPath()
    {
        return $this->filesystemPath;
    }

    /**
     * {@inheritdoc}
     */
    public function getMetadata()
    {
        return new FilesystemMetadata($this->filesystemPath);
    }

    protected function preSerialize(array &$data)
    {
        parent::preSerialize($data);

        $data[] = $this->filesystemPath;
    }

    protected function postUnserialize(array $data)
    {
        $this->filesystemPath = array_pop($data);

        parent::postUnserialize($data);
    }
}
