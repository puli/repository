<?php

/*
 * This file is part of the puli/repository package.
 *
 * (c) Bernhard Schussek <bschussek@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Puli\Repository\Resource\Metadata;

use Puli\Repository\Api\Resource\ResourceMetadata;

/**
 * @since  1.0
 * @author Bernhard Schussek <bschussek@gmail.com>
 */
class EmptyMetadata implements ResourceMetadata
{
    /**
     * {@inheritdoc}
     */
    public function getCreationTime()
    {
        return 0;
    }

    /**
     * {@inheritdoc}
     */
    public function getAccessTime()
    {
        return 0;
    }

    /**
     * {@inheritdoc}
     */
    public function getModificationTime()
    {
        return 0;
    }
}
