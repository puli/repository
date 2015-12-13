<?php

/*
 * This file is part of the puli/repository package.
 *
 * (c) Bernhard Schussek <bschussek@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Puli\Repository\ChangeStream\Normalizer;

use Puli\Repository\Api\Resource\PuliResource;
use Puli\Repository\Resource\DirectoryResource;

/**
 * Normalizer for file resources.
 *
 * @since  1.0
 *
 * @author Titouan Galopin <galopintitouan@gmail.com>
 */
class DirectoryResourceNormalizer extends AbstractFilesystemResourceNormalizer
{
    /**
     * {@inheritdoc}
     */
    public function denormalize($data)
    {
        return new DirectoryResource($data['filesystem_path'], $data['path']);
    }

    /**
     * {@inheritdoc}
     */
    public function supports(PuliResource $resource)
    {
        return $resource instanceof DirectoryResource;
    }
}
