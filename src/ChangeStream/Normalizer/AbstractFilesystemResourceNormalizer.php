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

use Puli\Repository\Api\Resource\FilesystemResource;
use Puli\Repository\Api\Resource\PuliResource;

/**
 * Abstract normalizer for filesystem resources.
 *
 * @since  1.0
 *
 * @author Titouan Galopin <galopintitouan@gmail.com>
 */
abstract class AbstractFilesystemResourceNormalizer extends GenericResourceNormalizer
{
    /**
     * {@inheritdoc}
     */
    public function normalize(PuliResource $resource)
    {
        /* @var FilesystemResource $resource */

        $data = parent::normalize($resource);
        $data['filesystem_path'] = $resource->getFilesystemPath();

        return $data;
    }
}
