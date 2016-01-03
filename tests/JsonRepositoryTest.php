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
use Puli\Repository\JsonRepository;
use Puli\Repository\Resource\DirectoryResource;

/**
 * @author Bernhard Schussek <bschussek@gmail.com>
 * @author Titouan Galopin <galopintitouan@gmail.com>
 */
class JsonRepositoryTest extends AbstractJsonRepositoryTest
{
    /**
     * Counter to avoid collisions during tests on directories.
     *
     * @var int
     */
    protected static $nextDirectoryId = 0;

    protected function createPrefilledRepository(PuliResource $root)
    {
        $repo = new JsonRepository($this->path, $this->tempDir, true);
        $repo->add('/', $root);

        return $repo;
    }

    protected function createWriteRepository()
    {
        return new JsonRepository($this->path, $this->tempDir, true);
    }

    protected function createReadRepository(EditableRepository $writeRepo)
    {
        return new JsonRepository($this->path, $this->tempDir, true);
    }

    /**
     * @expectedException \InvalidArgumentException
     */
    public function testRemoveFailsWhenPassingPathsThatAreNotMappings()
    {
        $this->writeRepo->add('/webmozart', new DirectoryResource($this->fixtureDir.'/dir1'));

        $this->writeRepo->remove('/webmozart/file1');
    }
}
