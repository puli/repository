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
use Puli\Repository\PathMappingRepository;
use Puli\Repository\Resource\DirectoryResource;
use Puli\Repository\Resource\FileResource;
use Puli\Repository\Resource\GenericResource;
use Webmozart\KeyValueStore\Api\KeyValueStore;
use Webmozart\KeyValueStore\ArrayStore;
use Webmozart\PathUtil\Path;

/**
 * @author Bernhard Schussek <bschussek@gmail.com>
 * @author Titouan Galopin <galopintitouan@gmail.com>
 */
class PathMappingRepositoryTest extends AbstractPathMappingRepositoryTest
{
    /**
     * Counter to avoid collisions during tests on directories.
     *
     * @var int
     */
    protected static $createdDirectories = 0;

    protected function createBaseDirectoryRepository(KeyValueStore $store, $baseDirectory)
    {
        return new PathMappingRepository($store, $baseDirectory);
    }

    protected function createPrefilledRepository(Resource $root)
    {
        $repo = new PathMappingRepository(new ArrayStore(), Path::getRoot(__DIR__));
        $repo->add('/', $root);

        return $repo;
    }

    protected function createWriteRepository()
    {
        return new PathMappingRepository(new ArrayStore(), Path::getRoot(__DIR__));
    }

    protected function createReadRepository(EditableRepository $writeRepo)
    {
        return $writeRepo;
    }

    public function testResolveMultipleBasePath()
    {
        $root = $this->buildStructure($this->createDirectory('', array(
            $this->createDirectory('/sub1', array(
                $this->createFile('/file1', 'original 1'),
            )),
            $this->createDirectory('/sub2', array(
                $this->createFile('/file2', 'original 2'),
            )),
        )));

        $this->writeRepo->add('/webmozart/sub1', new DirectoryResource($root->getFilesystemPath().'/sub2'));
        $this->writeRepo->add('/webmozart', new DirectoryResource($root->getFilesystemPath()));

        $this->assertTrue($this->writeRepo->contains('/webmozart'));
        $this->assertTrue($this->writeRepo->contains('/webmozart/sub1/file1'));
        $this->assertTrue($this->writeRepo->contains('/webmozart/sub1/file2'));
        $this->assertTrue($this->writeRepo->contains('/webmozart/sub2/file2'));
        $this->assertFalse($this->writeRepo->contains('/webmozart/sub2/file1'));
    }

    public function testResolveVirtualResource()
    {
        $this->writeRepo->add('/webmozart/foo/bar', new DirectoryResource(__DIR__.'/Fixtures/dir5'));

        $this->assertTrue($this->readRepo->contains('/webmozart'));
        $this->assertTrue($this->readRepo->contains('/webmozart/foo'));
        $this->assertTrue($this->readRepo->contains('/webmozart/foo/bar'));

        /** @var GenericResource $webmozart */
        $webmozart = $this->readRepo->get('/webmozart');

        /** @var GenericResource $foo */
        $foo = $this->readRepo->get('/webmozart/foo');

        /** @var DirectoryResource $bar */
        $bar = $this->readRepo->get('/webmozart/foo/bar');

        $this->assertInstanceOf('Puli\Repository\Resource\GenericResource', $webmozart);
        $this->assertInstanceOf('Puli\Repository\Resource\GenericResource', $foo);
        $this->assertInstanceOf('Puli\Repository\Resource\DirectoryResource', $bar);

        $this->assertEquals('/webmozart/foo', $webmozart->getChild('/foo')->getRepositoryPath());
        $this->assertEquals('/webmozart/foo/bar', $webmozart->getChild('/foo')->getChild('/bar')->getRepositoryPath());
        $this->assertEquals('/webmozart/foo/bar', $foo->getChild('/bar')->getRepositoryPath());

        $this->assertInstanceOf('Puli\Repository\Resource\DirectoryResource', $bar->getChild('/sub'));
        $this->assertInstanceOf('Puli\Repository\Resource\FileResource', $bar->getChild('/file1'));
        $this->assertCount(2, $bar->getChild('/sub')->listChildren()->toArray());
    }

    public function testResolveFilesystemResource()
    {
        $this->store->set('/', null);
        $this->store->set('/webmozart', null);
        $this->store->set('/webmozart/foo', array(__DIR__.'/Fixtures/dir5'));

        // Get
        $resource = $this->repo->get('/webmozart/foo/sub');

        $this->assertInstanceOf('Puli\Repository\Api\Resource\FilesystemResource', $resource);
        $this->assertEquals(Path::normalize(__DIR__.'/Fixtures/dir5/sub'), $resource->getFilesystemPath());

        // Find
        $resources = $this->repo->find('/**/sub');

        $this->assertCount(1, $resources);

        $resource = $resources->get(0);

        $this->assertInstanceOf('Puli\Repository\Api\Resource\FilesystemResource', $resource);
        $this->assertEquals(Path::normalize(__DIR__.'/Fixtures/dir5/sub'), $resource->getFilesystemPath());
    }

    public function testListVirtualResourceChildren()
    {
        $this->writeRepo->add('/webmozart', new DirectoryResource(__DIR__.'/Fixtures/dir5'));
        $this->writeRepo->add('/webmozart/foo', new DirectoryResource(__DIR__.'/Fixtures/dir5'));

        $dirlist = $this->writeRepo->listChildren('/webmozart');

        $this->assertCount(4, $dirlist);
        $this->assertEquals('/webmozart/file1', $dirlist->get(0)->getPath());
        $this->assertEquals('/webmozart/file2', $dirlist->get(1)->getPath());
        $this->assertEquals('/webmozart/sub', $dirlist->get(2)->getPath());
        $this->assertEquals('/webmozart/foo', $dirlist->get(3)->getPath());

        $dirlist = $this->writeRepo->listChildren('/webmozart/foo');

        $this->assertCount(3, $dirlist);
        $this->assertEquals('/webmozart/foo/file1', $dirlist->get(0)->getPath());
        $this->assertEquals('/webmozart/foo/file2', $dirlist->get(1)->getPath());
        $this->assertEquals('/webmozart/foo/sub', $dirlist->get(2)->getPath());
    }

    public function testFindVirtualResourceChildren()
    {
        $this->writeRepo->add('/webmozart', new DirectoryResource(__DIR__.'/Fixtures/dir5'));
        $this->writeRepo->add('/webmozart/foo', new DirectoryResource(__DIR__.'/Fixtures/dir5'));

        $dirlist = $this->writeRepo->find('/**/file1');

        $this->assertCount(2, $dirlist);
        $this->assertEquals('/webmozart/file1', $dirlist->get(0)->getPath());
        $this->assertEquals('/webmozart/foo/file1', $dirlist->get(1)->getPath());
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

        $file = $this->buildStructure($this->createFile('/webmozart/file'));
        $file->attachTo($otherRepo);

        $this->repo->add('/webmozart/file', $file);

        $this->assertNotSame($file, $this->repo->get('/webmozart/file'));
        $this->assertSame('/webmozart/file', $file->getPath());

        $clone = clone $file;
        $clone->attachTo($this->repo, '/webmozart/file');

        $this->assertEquals($clone, $this->repo->get('/webmozart/file'));
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

    public function testAddRelativePathInStore()
    {
        $repo = $this->createBaseDirectoryRepository($this->store, __DIR__.'/Fixtures');
        $repo->add('/webmozart/file', new FileResource(__DIR__.'/Fixtures/dir1/file1'));
        $repo->add('/webmozart/dir', new DirectoryResource(__DIR__.'/Fixtures/dir2'));

        $this->assertTrue($repo->contains('/webmozart/file'));
        $this->assertTrue($repo->contains('/webmozart/dir'));
        $this->assertEquals(array('dir1/file1'), $this->store->get('/webmozart/file'));
        $this->assertEquals(array('dir2'), $this->store->get('/webmozart/dir'));

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
        $this->store->set('/webmozart/dir', array('dir5'));
        $this->store->set('/webmozart/file', array('dir5/file1'));

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
