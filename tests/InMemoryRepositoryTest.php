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

use Puli\Repository\InMemoryRepository;
use Puli\Repository\Resource\Collection\ArrayResourceCollection;
use Puli\Repository\Resource\DirectoryResource;
use Puli\Repository\ResourceRepository;
use Puli\Repository\Tests\Resource\TestDirectory;
use Puli\Repository\Tests\Resource\TestFile;

/**
 * @since  1.0
 * @author Bernhard Schussek <bschussek@gmail.com>
 */
class InMemoryRepositoryTest extends AbstractManageableRepositoryTest
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

    protected function createRepository(DirectoryResource $root)
    {
        $repo = new InMemoryRepository();
        $repo->add('/', $root);

        return $repo;
    }

    protected function createManageableRepository(ResourceRepository $backend = null)
    {
        return new InMemoryRepository($backend);
    }

    public function testAddClonesResourcesAttachedToAnotherRepository()
    {
        $otherRepo = $this->getMock('Puli\Repository\ResourceRepository');

        $file = new TestFile('/file');
        $file->attachTo($otherRepo);

        $this->repo->add('/webmozart/puli/file', $file);

        $this->assertNotSame($file, $this->repo->get('/webmozart/puli/file'));
        $this->assertSame('/file', $file->getPath());

        $clone = clone $file;
        $clone->attachTo($this->repo, '/webmozart/puli/file');

        $this->assertEquals($clone, $this->repo->get('/webmozart/puli/file'));
    }

    public function testAddCollectionClonesEntriesAttachedToAnotherRepository()
    {
        $otherRepo = $this->getMock('Puli\Repository\ResourceRepository');

        $file1 = new TestFile('/file1');
        $file2 = new TestFile('/file2');

        $file2->attachTo($otherRepo);

        $this->repo->add('/webmozart/puli', new ArrayResourceCollection(array($file1, $file2)));

        $this->assertSame($file1, $this->repo->get('/webmozart/puli/file1'));
        $this->assertNotSame($file2, $this->repo->get('/webmozart/puli/file2'));
        $this->assertSame('/file2', $file2->getPath());

        $clone = clone $file2;
        $clone->attachTo($this->repo, '/webmozart/puli/file2');

        $this->assertEquals($clone, $this->repo->get('/webmozart/puli/file2'));
    }
}
