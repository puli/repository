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
use Puli\Repository\Api\Resource\Resource;
use Puli\Repository\OptimizedPathMappingRepository;
use Puli\Repository\Resource\DirectoryResource;
use Puli\Repository\Resource\FileResource;
use Puli\Repository\Tests\Resource\TestFilesystemDirectory;
use Puli\Repository\Tests\Resource\TestFilesystemFile;
use Webmozart\KeyValueStore\Api\KeyValueStore;
use Webmozart\KeyValueStore\ArrayStore;

/**
 * @author Bernhard Schussek <bschussek@gmail.com>
 * @author Titouan Galopin <galopintitouan@gmail.com>
 */
class OptimizedPathMappingRepositoryTest extends AbstractPathMappingRepositoryTest
{
    protected function createBaseDirectoryRepository(KeyValueStore $store, $baseDirectory)
    {
        return new OptimizedPathMappingRepository($store, $baseDirectory);
    }

    protected function createPrefilledRepository(Resource $root)
    {
        $repo = new OptimizedPathMappingRepository(new ArrayStore(), ('\\' === DIRECTORY_SEPARATOR) ? 'C:/' : '/');
        $repo->add('/', $root);

        return $repo;
    }

    protected function createWriteRepository()
    {
        return new OptimizedPathMappingRepository(new ArrayStore(), ('\\' === DIRECTORY_SEPARATOR) ? 'C:/' : '/');
    }

    protected function createReadRepository(EditableRepository $writeRepo)
    {
        return $writeRepo;
    }

    protected function createFile($path = null, $body = TestFilesystemFile::BODY)
    {
        $filesystemPath = $this->tempDir.'/file'.self::$createdFiles;

        file_put_contents($filesystemPath, $body);
        ++self::$createdFiles;

        return new FileResource($filesystemPath, $path);
    }

    protected function createDirectory($path = null, array $children = array())
    {
        return new TestFilesystemDirectory($path, $children);
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

        $file = $this->createFile('/file');
        $file->attachTo($otherRepo);

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
        $this->assertPathsEquals(__DIR__.'/Fixtures/dir1/file1', $repo->get('/webmozart/file')->getFilesystemPath());
        $this->assertPathsEquals(__DIR__.'/Fixtures/dir2', $repo->get('/webmozart/dir')->getFilesystemPath());
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
        $this->assertPathsEquals(__DIR__.'/Fixtures/dir5', $repo->get('/webmozart/dir')->getFilesystemPath());
        $this->assertPathsEquals(__DIR__.'/Fixtures/dir5/file1', $repo->get('/webmozart/file')->getFilesystemPath());
    }
}
