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
use Puli\Repository\ChangeStream\Normalizer\ResourceNormalizer;
use Puli\Repository\Tests\Resource\TestNullResource;

/**
 * @author Titouan Galopin <galopintitouan@gmail.com>
 */
abstract class AbstractResourceNormalizerTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @return ResourceNormalizer
     */
    abstract public function createNormalizer();

    /**
     * @return PuliResource
     */
    abstract public function createSupportedResource();

    public function testNormalize()
    {
        $this->assertInternalType('array', $this->createNormalizer()->normalize($this->createSupportedResource()));
    }

    public function testNormalizeDenormalize()
    {
        $normalizer = $this->createNormalizer();

        $resource = $this->createSupportedResource();
        $normalized = $normalizer->denormalize($normalizer->normalize($resource));

        $this->assertEquals($resource->getPath(), $normalized->getPath());
        $this->assertTrue($normalizer->supports($normalized));
    }

    public function testUnsupportedResource()
    {
        $this->assertFalse($this->createNormalizer()->supports(new TestNullResource()));
    }
}
