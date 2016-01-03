<?php

/*
 * This file is part of the puli/repository package.
 *
 * (c) Bernhard Schussek <bschussek@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Puli\Repository\Tests;

use Puli\Repository\Api\EditableRepository;
use Puli\Repository\Api\Resource\PuliResource;
use Puli\Repository\OptimizedJsonRepository;

/**
 * @author Bernhard Schussek <bschussek@gmail.com>
 * @author Titouan Galopin <galopintitouan@gmail.com>
 */
class OptimizedJsonRepositoryTest extends AbstractJsonRepositoryTest
{
    protected function createPrefilledRepository(PuliResource $root)
    {
        $repo = new OptimizedJsonRepository($this->path, $this->tempDir, true);
        $repo->add('/', $root);

        return $repo;
    }

    protected function createWriteRepository()
    {
        return new OptimizedJsonRepository($this->path, $this->tempDir, true, $this->stream);
    }

    protected function createReadRepository(EditableRepository $writeRepo)
    {
        return new OptimizedJsonRepository($this->path, $this->tempDir, true, $this->stream);
    }
}
