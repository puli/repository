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
use Puli\Repository\InMemoryRepository;

/**
 * @author Bernhard Schussek <bschussek@gmail.com>
 */
class InMemoryRepositoryTest extends AbstractEditableRepositoryTest
{
    /**
     * @var InMemoryRepository
     */
    protected $repo;

    protected function setUp()
    {
        parent::setUp();

        $this->repo = new InMemoryRepository();
    }

    protected function createPrefilledRepository(PuliResource $root)
    {
        $repo = new InMemoryRepository();
        $repo->add('/', $root);

        return $repo;
    }

    protected function createWriteRepository()
    {
        return new InMemoryRepository($this->stream);
    }

    protected function createReadRepository(EditableRepository $writeRepo)
    {
        return $writeRepo;
    }

    /**
     * @expectedException \Puli\Repository\Api\UnsupportedLanguageException
     * @expectedExceptionMessage foobar
     */
    public function testContainsFailsIfLanguageNotGlob()
    {
        $this->readRepo->contains('/*', 'foobar');
    }

    /**
     * @expectedException \Puli\Repository\Api\UnsupportedLanguageException
     * @expectedExceptionMessage foobar
     */
    public function testFindFailsIfLanguageNotGlob()
    {
        $this->readRepo->find('/*', 'foobar');
    }

    /**
     * @expectedException \Puli\Repository\Api\UnsupportedLanguageException
     * @expectedExceptionMessage foobar
     */
    public function testRemoveFailsIfLanguageNotGlob()
    {
        $this->writeRepo->remove('/*', 'foobar');
    }
}
