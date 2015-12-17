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

use Puli\Repository\Api\ChangeStream\ChangeStream;
use Puli\Repository\Api\EditableRepository;
use Puli\Repository\Api\Resource\PuliResource;
use Puli\Repository\OptimizedJsonRepository;
use Puli\Repository\Resource\DirectoryResource;
use Puli\Repository\Resource\FileResource;
use Webmozart\KeyValueStore\Api\KeyValueStore;
use Webmozart\KeyValueStore\ArrayStore;
use Webmozart\PathUtil\Path;

/**
 * @author Bernhard Schussek <bschussek@gmail.com>
 * @author Titouan Galopin <galopintitouan@gmail.com>
 */
class OptimizedJsonRepositoryTest extends AbstractJsonRepositoryTest
{
    protected function createBaseDirectoryRepository(KeyValueStore $store, $baseDirectory)
    {
        return new OptimizedJsonRepository($store, $baseDirectory);
    }

    protected function createPrefilledRepository(PuliResource $root)
    {
        $repo = new OptimizedJsonRepository(new ArrayStore(), Path::getRoot(__DIR__));
        $repo->add('/', $root);

        return $repo;
    }

    protected function createWriteRepository(ChangeStream $stream = null)
    {
        return new OptimizedJsonRepository(new ArrayStore(), Path::getRoot(__DIR__), $stream);
    }

    protected function createReadRepository(EditableRepository $writeRepo)
    {
        return $writeRepo;
    }

    public function testAddDirectoryCompletelyResolveChildren()
    {
        $this->writeRepo->add('/webmozart', new DirectoryResource(__DIR__.'/Fixtures/dir5'));

        $this->assertTrue($this->readRepo->contains('/webmozart'));
        $this->assertTrue($this->readRepo->contains('/webmozart/file1'));
        $this->assertTrue($this->readRepo->contains('/webmozart/file2'));
        $this->assertTrue($this->readRepo->contains('/webmozart/sub'));
        $this->assertTrue($this->readRepo->contains('/webmozart/sub/file3'));
        $this->assertTrue($this->readRepo->contains('/webmozart/sub/file4'));
    }

    public function testAddClonesResourcesAttachedToAnotherRepository()
    {
        $otherRepo = $this->getMock('Puli\Repository\Api\ResourceRepository');

        $file = new FileResource(__DIR__.'/Fixtures/dir1/file1');
        $file->attachTo($otherRepo, '/file');

        $this->repo->add('/webmozart/puli/file', $file);

        $this->assertNotSame($file, $this->repo->get('/webmozart/puli/file'));
        $this->assertSame('/file', $file->getPath());

        $clone = clone $file;
        $clone->attachTo($this->repo, '/webmozart/puli/file');

        $this->assertEquals($clone, $this->repo->get('/webmozart/puli/file'));
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

    public function testAddRelativePathInStore()
    {
        $repo = $this->createBaseDirectoryRepository($this->store, __DIR__.'/Fixtures');
        $repo->add('/webmozart/file', new FileResource(__DIR__.'/Fixtures/dir1/file1'));
        $repo->add('/webmozart/dir', new DirectoryResource(__DIR__.'/Fixtures/dir2'));

        $this->assertTrue($repo->contains('/webmozart/file'));
        $this->assertTrue($repo->contains('/webmozart/dir'));
        $this->assertEquals('dir1/file1', $this->store->get('/webmozart/file'));
        $this->assertEquals('dir2', $this->store->get('/webmozart/dir'));

        $this->assertEquals(
            Path::normalize(__DIR__.'/Fixtures/dir1/file1'),
            $repo->get('/webmozart/file')->getFilesystemPath()
        );

        $this->assertEquals(
            Path::normalize(__DIR__.'/Fixtures/dir2'),
            $repo->get('/webmozart/dir')->getFilesystemPath()
        );
    }

    public function testCreateWithFilledStore()
    {
        $this->store->set('/webmozart/dir', 'dir5');
        $this->store->set('/webmozart/file', 'dir5/file1');

        $repo = $this->createBaseDirectoryRepository($this->store, __DIR__.'/Fixtures');

        $this->assertTrue($repo->contains('/webmozart/dir'));
        $this->assertTrue($repo->contains('/webmozart/file'));
        $this->assertInstanceOf('Puli\Repository\Resource\DirectoryResource', $repo->get('/webmozart/dir'));
        $this->assertInstanceOf('Puli\Repository\Resource\FileResource', $repo->get('/webmozart/file'));

        $this->assertEquals(
            Path::normalize(__DIR__.'/Fixtures/dir5'),
            $repo->get('/webmozart/dir')->getFilesystemPath()
        );

        $this->assertEquals(
            Path::normalize(__DIR__.'/Fixtures/dir5/file1'),
            $repo->get('/webmozart/file')->getFilesystemPath()
        );
    }
}
