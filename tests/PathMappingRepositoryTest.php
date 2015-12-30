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

    protected function createPrefilledRepository(PuliResource $root)
    {
        $repo = new PathMappingRepository(new ArrayStore(), Path::getRoot(__DIR__));
        $repo->add('/', $root);

        return $repo;
    }

    protected function createWriteRepository(ChangeStream $stream = null)
    {
        return new PathMappingRepository(new ArrayStore(), Path::getRoot(__DIR__), $stream);
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

    public function testMergeDirectParent()
    {
        $root = __DIR__.'/Fixtures/dir5';

        $this->store->set('/', null);
        $this->store->set('/webmozart', array($root));
        $this->store->set('/webmozart/sub', null);
        $this->store->set('/webmozart/sub/file1', array($root.'/file1'));

        /*
         * /webmozart/sub should have three children:
         *      -   file1 from the virtual children
         *          (directly resolve /webmozart/sub/file1 to /Fixtures/dir5/file1)
         *      -   file3 and file4 from the real children
         *          (resolve /webmozart to /Fixtures/dir5 and then list children of /Fixtures/dir5/sub)
         */
        $resources = $this->repo->listChildren('/webmozart/sub');
        $this->assertCount(3, $resources);

        $paths = $resources->getPaths();
        sort($paths);

        $this->assertSame('/webmozart/sub/file1', $paths[0]);
        $this->assertSame('/webmozart/sub/file3', $paths[1]);
        $this->assertSame('/webmozart/sub/file4', $paths[2]);
    }

    public function testMergeSubParents()
    {
        $root = __DIR__.'/Fixtures';

        $this->store->set('/', null);
        $this->store->set('/webmozart', array($root));
        $this->store->set('/webmozart/dir5', null);
        $this->store->set('/webmozart/dir5/sub', null);
        $this->store->set('/webmozart/dir5/sub/file1', array($root.'/file1'));

        /*
         * /webmozart/dir5/sub should have three children:
         *      -   file1 from the virtual children
         *          (directly resolve /webmozart/dir5/sub/file1 to /Fixtures/dir5/file1)
         *      -   file3 and file4 from the real children
         *          (resolve /webmozart to /Fixtures and then list children of /Fixtures/dir5/sub)
         */
        $resources = $this->repo->listChildren('/webmozart/dir5/sub');
        $this->assertCount(3, $resources);

        $paths = $resources->getPaths();
        sort($paths);

        $this->assertSame('/webmozart/dir5/sub/file1', $paths[0]);
        $this->assertSame('/webmozart/dir5/sub/file3', $paths[1]);
        $this->assertSame('/webmozart/dir5/sub/file4', $paths[2]);
    }

    public function testMergeMultipleParents()
    {
        $root = __DIR__.'/Fixtures';

        $this->store->set('/', null);
        $this->store->set('/webmozart', array($root, $root.'/dir3', $root.'/dir4', $root.'/dir5', $root.'/dir1'));
        $this->store->set('/webmozart/sub', null);
        $this->store->set('/webmozart/sub/virtualfile1', array($root.'/dir1/file1'));
        $this->store->set('/webmozart/sub/virtualfile2', array($root.'/dir1/file2'));

        /*
         * /webmozart/sub should be resolved to:
         *      /Fixtures/sub
         *      /Fixtures/dir3/sub
         *      /Fixtures/dir4/sub
         *      /Fixtures/dir5/sub
         *      /Fixtures/dir1/sub
         *
         * As /Fixtures/sub and /Fixtures/dir1/sub don't have children and
         * there are two virtual files under /webmozart/sub, the listing of
         * children of /webmozart/sub shoudl return:
         *      /Fixtures/dir3/sub/file1
         *      /Fixtures/dir4/sub/file2 (override /Fixtures/dir3/sub/file2)
         *      /Fixtures/dir5/sub/file3 (override /Fixtures/dir4/sub/file3)
         *      /Fixtures/dir5/sub/file4
         *      /Fixtures/dir1/file1 (from first virtual file)
         *      /Fixtures/dir1/file2 (from second virtual file)
         */
        $resources = $this->repo->listChildren('/webmozart/sub');
        $this->assertCount(6, $resources);

        $paths = $resources->getPaths();
        sort($paths);

        $this->assertEquals('/webmozart/sub/file1', $paths[0]);
        $this->assertEquals('/webmozart/sub/file2', $paths[1]);
        $this->assertEquals('/webmozart/sub/file3', $paths[2]);
        $this->assertEquals('/webmozart/sub/file4', $paths[3]);
        $this->assertEquals('/webmozart/sub/virtualfile1', $paths[4]);
        $this->assertEquals('/webmozart/sub/virtualfile2', $paths[5]);
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
