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
use Puli\Repository\ChangeStream\Normalizer\DirectoryResourceNormalizer;
use Puli\Repository\Resource\DirectoryResource;

/**
 * @author Titouan Galopin <galopintitouan@gmail.com>
 */
class DirectoryResourceNormalizerTest extends AbstractFilesystemResourceNormalizerTest
{
    /**
     * @return DirectoryResourceNormalizer
     */
    public function createNormalizer()
    {
        return new DirectoryResourceNormalizer();
    }

    /**
     * @return PuliResource
     */
    public function createSupportedResource()
    {
        return new DirectoryResource(__DIR__.'/../../Fixtures/dir1', '/supported');
    }
}
