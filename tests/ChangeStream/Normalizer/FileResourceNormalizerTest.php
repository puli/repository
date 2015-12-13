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
use Puli\Repository\ChangeStream\Normalizer\FileResourceNormalizer;
use Puli\Repository\Resource\FileResource;

/**
 * @author Titouan Galopin <galopintitouan@gmail.com>
 */
class FileResourceNormalizerTest extends AbstractFilesystemResourceNormalizerTest
{
    /**
     * @return FileResourceNormalizer
     */
    public function createNormalizer()
    {
        return new FileResourceNormalizer();
    }

    /**
     * @return PuliResource
     */
    public function createSupportedResource()
    {
        return new FileResource(__DIR__.'/../../Fixtures/dir1/file1', '/supported');
    }
}
