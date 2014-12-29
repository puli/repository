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

use Puli\Repository\Api\Resource\LocalResource;

/**
 * Base class for local resources.
 *
 * @since  1.0
 * @author Bernhard Schussek <bschussek@gmail.com>
 */
abstract class AbstractLocalResource extends AbstractResource implements LocalResource
{
    /**
     * @var string
     */
    private $localPath;

    /**
     * Creates a new local resource.
     *
     * @param string      $localPath The path on the local file system.
     * @param string|null $path      The repository path of the resource.
     */
    public function __construct($localPath, $path = null)
    {
        parent::__construct($path);

        $this->localPath = $localPath;
    }

    /**
     * {@inheritdoc}
     */
    public function getLocalPath()
    {
        return $this->localPath;
    }

    protected function preSerialize(array &$data)
    {
        parent::preSerialize($data);

        $data[] = $this->localPath;
    }

    protected function postUnserialize(array $data)
    {
        $this->localPath = array_pop($data);

        parent::postUnserialize($data);
    }
}
