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
use Symfony\Component\Filesystem\Filesystem;
use Webmozart\Glob\Test\TestUtil;
use Webmozart\KeyValueStore\ArrayStore;

/**
 * @author Bernhard Schussek <bschussek@gmail.com>
 * @author Titouan Galopin <galopintitouan@gmail.com>
 */
class OptimizedPathMappingRepositoryTest extends AbstractEditableRepositoryTest
{
    /**
     * @var ArrayStore
     */
    protected $store;

    /**
     * @var OptimizedPathMappingRepository
     */
    protected $repo;

    /**
     * Temporary directory for test filess.
     *
     * @var string
     */
    protected $tempDir;

    /**
     * Counter to avoid collisions during tests on files.
     *
     * @var int
     */
    protected static $createdFiles = 0;

    protected function setUp()
    {
        parent::setUp();

        $this->tempDir = TestUtil::makeTempDir('puli-repository', __CLASS__);
        $this->store = new ArrayStore();
        $this->repo = new OptimizedPathMappingRepository($this->store);
    }

    protected function tearDown()
    {
        parent::tearDown();

        $filesystem = new Filesystem();
        $filesystem->remove($this->tempDir);
    }

    protected function createPrefilledRepository(Resource $root)
    {
        $repo = new OptimizedPathMappingRepository(new ArrayStore());
        $repo->add('/', $root);

        return $repo;
    }

    protected function createWriteRepository()
    {
        return new OptimizedPathMappingRepository(new ArrayStore());
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

    public function testCreateWithFilledStore()
    {
        $store = new ArrayStore();
        $store->set('/webmozart', new DirectoryResource(__DIR__.'/Fixtures/dir5'));
        $store->set('/webmozart/file1', new FileResource(__DIR__.'/Fixtures/dir5/file1'));

        $repo = new OptimizedPathMappingRepository($store);

        $this->assertTrue($repo->contains('/webmozart'));
        $this->assertTrue($repo->contains('/webmozart/file1'));
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
}
