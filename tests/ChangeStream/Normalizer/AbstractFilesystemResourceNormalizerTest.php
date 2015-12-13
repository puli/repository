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

/**
 * @author Titouan Galopin <galopintitouan@gmail.com>
 */
abstract class AbstractFilesystemResourceNormalizerTest extends AbstractResourceNormalizerTest
{
    public function testNormalizeDenormalizeFilesystem()
    {
        $normalizer = $this->createNormalizer();

        $resource = $this->createSupportedResource();
        $normalized = $normalizer->denormalize($normalizer->normalize($resource));

        $this->assertEquals($resource->getFilesystemPath(), $normalized->getFilesystemPath());
    }
}
