<?php

/*
 * This file is part of the puli/repository package.
 *
 * (c) Bernhard Schussek <bschussek@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Puli\Repository\Tests\ChangeStream\Normalizer;

use Puli\Repository\Api\Resource\PuliResource;
use Puli\Repository\ChangeStream\Normalizer\GenericResourceNormalizer;
use Puli\Repository\Resource\DirectoryResource;

/**
 * @author Titouan Galopin <galopintitouan@gmail.com>
 */
class GenericResourceNormalizerTest extends AbstractResourceNormalizerTest
{
    /**
     * @return GenericResourceNormalizer
     */
    public function createNormalizer()
    {
        return new GenericResourceNormalizer();
    }

    /**
     * @return PuliResource
     */
    public function createSupportedResource()
    {
        return new DirectoryResource(__DIR__.'/../../Fixtures/dir1', '/supported');
    }
}
