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
use Puli\Repository\JsonRepository;
use Puli\Repository\Resource\DirectoryResource;
use Puli\Repository\Resource\FileResource;
use Puli\Repository\Resource\GenericResource;
use Webmozart\PathUtil\Path;

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
    protected static $createdDirectories = 0;

    protected function createPrefilledRepository(PuliResource $root)
    {
        $repo = new JsonRepository($this->path, $this->tempDir);
        $repo->add('/', $root);

        return $repo;
    }

    protected function createWriteRepository(ChangeStream $stream = null)
    {
        return new JsonRepository($this->path, $this->tempDir, $stream);
    }

    protected function createReadRepository(EditableRepository $writeRepo)
    {
        return $writeRepo;
    }

    public function testResolveMultipleBasePath()
    {
        $root = $this->prepareFixtures($this->createDirectory('', array(
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
        $this->writeRepo->add('/webmozart/foo/bar', new DirectoryResource($this->fixtureDir.'/dir5'));

        $this->assertTrue($this->writeRepo->contains('/webmozart'));
        $this->assertTrue($this->writeRepo->contains('/webmozart/foo'));
        $this->assertTrue($this->writeRepo->contains('/webmozart/foo/bar'));

        /** @var GenericResource $webmozart */
        $webmozart = $this->writeRepo->get('/webmozart');

        /** @var GenericResource $foo */
        $foo = $this->writeRepo->get('/webmozart/foo');

        /** @var DirectoryResource $bar */
        $bar = $this->writeRepo->get('/webmozart/foo/bar');

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
        $json = <<<JSON
{
    "/webmozart/foo": ["fixtures/dir5"]
}

JSON;

        file_put_contents($this->path, $json);

        $this->writeRepo = $this->createWriteRepository();

        // Get
        $resource = $this->writeRepo->get('/webmozart/foo/sub');

        $this->assertInstanceOf('Puli\Repository\Api\Resource\FilesystemResource', $resource);
        $this->assertEquals(Path::normalize($this->fixtureDir.'/dir5/sub'), $resource->getFilesystemPath());

        // Find
        $resources = $this->writeRepo->find('/**/sub');

        $this->assertCount(1, $resources);

        $resource = $resources->get(0);

        $this->assertInstanceOf('Puli\Repository\Api\Resource\FilesystemResource', $resource);
        $this->assertEquals(Path::normalize($this->fixtureDir.'/dir5/sub'), $resource->getFilesystemPath());
    }

    public function testMergeDirectParent()
    {
        $json = <<<JSON
{
    "/webmozart": ["fixtures/dir5"],
    "/webmozart/sub/file1": ["fixtures/dir5/file1"]
}

JSON;

        file_put_contents($this->path, $json);

        $this->writeRepo = $this->createWriteRepository();

        /*
         * /webmozart/sub should have three children:
         *      -   file1 from the virtual children
         *          (directly resolve /webmozart/sub/file1 to /Fixtures/dir5/file1)
         *      -   file3 and file4 from the real children
         *          (resolve /webmozart to /Fixtures/dir5 and then list children of /Fixtures/dir5/sub)
         */
        $resources = $this->writeRepo->listChildren('/webmozart/sub');
        $this->assertCount(3, $resources);

        $paths = $resources->getPaths();
        sort($paths);

        $this->assertSame('/webmozart/sub/file1', $paths[0]);
        $this->assertSame('/webmozart/sub/file3', $paths[1]);
        $this->assertSame('/webmozart/sub/file4', $paths[2]);
    }

    public function testMergeSubParents()
    {
        $json = <<<JSON
{
    "/webmozart": ["fixtures"],
    "/webmozart/dir5/sub/file1": ["fixtures/dir5/file1"]
}

JSON;

        file_put_contents($this->path, $json);

        $this->writeRepo = $this->createWriteRepository();

        /*
         * /webmozart/dir5/sub should have three children:
         *      -   file1 from the virtual children
         *          (directly resolve /webmozart/dir5/sub/file1 to /Fixtures/dir5/file1)
         *      -   file3 and file4 from the real children
         *          (resolve /webmozart to /Fixtures and then list children of /Fixtures/dir5/sub)
         */
        $resources = $this->writeRepo->listChildren('/webmozart/dir5/sub');
        $this->assertCount(3, $resources);

        $paths = $resources->getPaths();
        sort($paths);

        $this->assertSame('/webmozart/dir5/sub/file1', $paths[0]);
        $this->assertSame('/webmozart/dir5/sub/file3', $paths[1]);
        $this->assertSame('/webmozart/dir5/sub/file4', $paths[2]);
    }

    public function testMergeMultipleParents()
    {
        $json = <<<JSON
{
    "/webmozart": ["fixtures", "fixtures/dir3", "fixtures/dir4", "fixtures/dir5", "fixtures/dir1"],
    "/webmozart/sub/virtualfile1": ["fixtures/dir1/file1"],
    "/webmozart/sub/virtualfile2": ["fixtures/dir1/file2"]
}

JSON;

        file_put_contents($this->path, $json);

        $this->writeRepo = $this->createWriteRepository();

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
        $resources = $this->writeRepo->listChildren('/webmozart/sub');
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
        $this->writeRepo->add('/webmozart', new DirectoryResource($this->fixtureDir.'/dir5'));
        $this->writeRepo->add('/webmozart/foo', new DirectoryResource($this->fixtureDir.'/dir5'));

        $dirlist = $this->writeRepo->listChildren('/webmozart');

        $this->assertCount(4, $dirlist);
        $this->assertEquals('/webmozart/file1', $dirlist->get(0)->getPath());
        $this->assertEquals('/webmozart/file2', $dirlist->get(1)->getPath());
        $this->assertEquals('/webmozart/foo', $dirlist->get(2)->getPath());
        $this->assertEquals('/webmozart/sub', $dirlist->get(3)->getPath());

        $dirlist = $this->writeRepo->listChildren('/webmozart/foo');

        $this->assertCount(3, $dirlist);
        $this->assertEquals('/webmozart/foo/file1', $dirlist->get(0)->getPath());
        $this->assertEquals('/webmozart/foo/file2', $dirlist->get(1)->getPath());
        $this->assertEquals('/webmozart/foo/sub', $dirlist->get(2)->getPath());
    }

    public function testFindVirtualResourceChildren()
    {
        $this->writeRepo->add('/webmozart', new DirectoryResource($this->fixtureDir.'/dir5'));
        $this->writeRepo->add('/webmozart/foo', new DirectoryResource($this->fixtureDir.'/dir5'));

        $dirlist = $this->writeRepo->find('/**/file1');

        $this->assertCount(2, $dirlist);
        $this->assertEquals('/webmozart/file1', $dirlist->get(0)->getPath());
        $this->assertEquals('/webmozart/foo/file1', $dirlist->get(1)->getPath());
    }

    public function testAddDirectoryCompletelyResolveChildren()
    {
        $this->writeRepo->add('/webmozart', new DirectoryResource($this->fixtureDir.'/dir5'));

        $this->assertTrue($this->writeRepo->contains('/webmozart'));
        $this->assertTrue($this->writeRepo->contains('/webmozart/file1'));
        $this->assertTrue($this->writeRepo->contains('/webmozart/file2'));
        $this->assertTrue($this->writeRepo->contains('/webmozart/sub'));
        $this->assertTrue($this->writeRepo->contains('/webmozart/sub/file3'));
        $this->assertTrue($this->writeRepo->contains('/webmozart/sub/file4'));
    }

    public function testAddClonesResourcesAttachedToAnotherRepository()
    {
        $otherRepo = $this->getMock('Puli\Repository\Api\ResourceRepository');

        $file = $this->prepareFixtures($this->createFile('/webmozart/file'));
        $file->attachTo($otherRepo);

        $this->writeRepo->add('/webmozart/file', $file);

        $this->assertNotSame($file, $this->writeRepo->get('/webmozart/file'));
        $this->assertSame('/webmozart/file', $file->getPath());

        $clone = clone $file;
        $clone->attachTo($this->writeRepo, '/webmozart/file');

        $this->assertEquals($clone, $this->writeRepo->get('/webmozart/file'));
    }

    /**
     * @expectedException \Puli\Repository\Api\UnsupportedLanguageException
     * @expectedExceptionMessage foobar
     */
    public function testContainsFailsIfLanguageNotGlob()
    {
        $this->writeRepo->contains('/*', 'foobar');
    }

    /**
     * @expectedException \Puli\Repository\Api\UnsupportedLanguageException
     * @expectedExceptionMessage foobar
     */
    public function testFindFailsIfLanguageNotGlob()
    {
        $this->writeRepo->find('/*', 'foobar');
    }

    public function testAddRelativePathInStore()
    {
        $this->writeRepo = $this->createWriteRepository();
        $this->writeRepo->add('/webmozart/file', new FileResource($this->fixtureDir.'/dir1/file1'));
        $this->writeRepo->add('/webmozart/dir', new DirectoryResource($this->fixtureDir.'/dir2'));

        $this->assertTrue($this->writeRepo->contains('/webmozart/file'));
        $this->assertTrue($this->writeRepo->contains('/webmozart/dir'));
//        $this->assertEquals(array('dir1/file1'), $this->store->get('/webmozart/file'));
//        $this->assertEquals(array('dir2'), $this->store->get('/webmozart/dir'));

        $this->assertEquals(
            Path::normalize($this->fixtureDir.'/dir1/file1'),
            $this->writeRepo->get('/webmozart/file')->getFilesystemPath()
        );

        $this->assertEquals(
            Path::normalize($this->fixtureDir.'/dir2'),
            $this->writeRepo->get('/webmozart/dir')->getFilesystemPath()
        );
    }

    public function testCreateWithFilledStore()
    {
        $json = <<<JSON
{
    "/webmozart/dir": ["fixtures/dir5"],
    "/webmozart/file": ["fixtures/dir5/file1"]
}

JSON;

        file_put_contents($this->path, $json);

        $this->writeRepo = $this->createWriteRepository();

        $this->assertTrue($this->writeRepo->contains('/webmozart/dir'));
        $this->assertTrue($this->writeRepo->contains('/webmozart/file'));
        $this->assertInstanceOf('Puli\Repository\Resource\DirectoryResource', $this->writeRepo->get('/webmozart/dir'));
        $this->assertInstanceOf('Puli\Repository\Resource\FileResource', $this->writeRepo->get('/webmozart/file'));

        $this->assertEquals(
            Path::normalize($this->fixtureDir.'/dir5'),
            $this->writeRepo->get('/webmozart/dir')->getFilesystemPath()
        );

        $this->assertEquals(
            Path::normalize($this->fixtureDir.'/dir5/file1'),
            $this->writeRepo->get('/webmozart/file')->getFilesystemPath()
        );
    }
}
