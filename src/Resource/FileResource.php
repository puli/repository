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

use Assert\Assertion;
use Puli\Repository\Api\Resource\BodyResource;
use Puli\Repository\Api\ResourceNotFoundException;
use Puli\Repository\Resource\Collection\ArrayResourceCollection;

/**
 * Represents a file on the file system.
 *
 * @since  1.0
 * @author Bernhard Schussek <bschussek@gmail.com>
 */
class FileResource extends AbstractFilesystemResource implements BodyResource
{
    /**
     * {@inheritdoc}
     */
    public function __construct($filesystemPath, $path = null)
    {
        Assertion::file($filesystemPath);

        parent::__construct($filesystemPath, $path);
    }

    /**
     * {@inheritdoc}
     */
    public function getBody()
    {
        return file_get_contents($this->getFilesystemPath());
    }

    /**
     * {@inheritdoc}
     */
    public function getChild($relPath)
    {
        throw ResourceNotFoundException::forPath($this->getPath().'/'.$relPath);
    }

    /**
     * {@inheritdoc}
     */
    public function hasChild($relPath)
    {
        return false;
    }

    /**
     * {@inheritdoc}
     */
    public function hasChildren()
    {
        return false;
    }

    /**
     * {@inheritdoc}
     */
    public function listChildren()
    {
        return new ArrayResourceCollection();
    }
}
