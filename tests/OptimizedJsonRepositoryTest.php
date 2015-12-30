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
use Webmozart\PathUtil\Path;

/**
 * @author Bernhard Schussek <bschussek@gmail.com>
 * @author Titouan Galopin <galopintitouan@gmail.com>
 */
class OptimizedJsonRepositoryTest extends AbstractJsonRepositoryTest
{
    protected function createPrefilledRepository(PuliResource $root)
    {
        $repo = new OptimizedJsonRepository($this->path, $this->tempDir, null, true);
        $repo->add('/', $root);

        return $repo;
    }

    protected function createWriteRepository(ChangeStream $stream = null)
    {
        return new OptimizedJsonRepository($this->path, $this->tempDir, $stream, true);
    }

    protected function createReadRepository(EditableRepository $writeRepo)
    {
        return $writeRepo;
    }

    public function testAddDirectoryCompletelyResolveChildren()
    {
        $this->writeRepo->add('/webmozart', new DirectoryResource($this->fixtureDir.'/dir5'));

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

        $file = new FileResource($this->fixtureDir.'/dir1/file1');
        $file->attachTo($otherRepo, '/file');

        $this->writeRepo->add('/webmozart/puli/file', $file);

        $this->assertNotSame($file, $this->writeRepo->get('/webmozart/puli/file'));
        $this->assertSame('/file', $file->getPath());

        $clone = clone $file;
        $clone->attachTo($this->writeRepo, '/webmozart/puli/file');

        $this->assertEquals($clone, $this->writeRepo->get('/webmozart/puli/file'));
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
        $this->writeRepo->add('/webmozart/file', new FileResource($this->fixtureDir.'/dir1/file1'));
        $this->writeRepo->add('/webmozart/dir', new DirectoryResource($this->fixtureDir.'/dir2'));

        $this->assertTrue($this->writeRepo->contains('/webmozart/file'));
        $this->assertTrue($this->writeRepo->contains('/webmozart/dir'));
//        $this->assertEquals('dir1/file1', $this->store->get('/webmozart/file'));
//        $this->assertEquals('dir2', $this->store->get('/webmozart/dir'));

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
    "/": null,
    "/webmozart": null,
    "/webmozart/dir": "fixtures/dir5",
    "/webmozart/file": "fixtures/dir5/file1"
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
