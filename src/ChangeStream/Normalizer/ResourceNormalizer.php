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

/**
 * A ResourceNormalizer normalizes PuliResource objects into arrays
 * and denormalizes arrays into PuliResource objects.
 *
 * @since  1.0
 *
 * @author Titouan Galopin <galopintitouan@gmail.com>
 */
interface ResourceNormalizer
{
    /**
     * Normalizes a resource into an array.
     *
     * @param PuliResource $resource
     *
     * @return array
     */
    public function normalize(PuliResource $resource);

    /**
     * Denormalizes an array back into a PuliResource object.
     *
     * @param array $data Resource array representation
     *
     * @return PuliResource
     */
    public function denormalize($data);

    /**
     * Checks whether the given resource is supported by this normalizer.
     *
     * @param PuliResource $resource
     *
     * @return bool
     */
    public function supports(PuliResource $resource);
}
