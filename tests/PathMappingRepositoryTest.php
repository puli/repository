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
use Puli\Repository\Tests\Resource\TestFilesystemDirectory;
use Puli\Repository\Tests\Resource\TestFilesystemFile;
use Symfony\Component\Filesystem\Filesystem;
use Webmozart\Glob\Test\TestUtil;
use Webmozart\KeyValueStore\ArrayStore;

/**
 * @since  1.0
 *
 * @author Bernhard Schussek <bschussek@gmail.com>
 * @author Titouan Galopin <galopintitouan@gmail.com>
 */
class PathMappingRepositoryTest extends AbstractEditableRepositoryTest
{
    /**
     * @var ArrayStore
     */
    protected $store;

    /**
     * @var PathMappingRepository
     */
    protected $repo;

    /**
     * Temporary directory for test files.
     *
     * @var string
     */
    protected $tempDir;

    /**
     * Counter to avoid collisions during tests on directories.
     *
     * @var int
     */
    protected static $createdDirectories = 0;

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
        $this->repo = new PathMappingRepository($this->store);
    }

    protected function tearDown()
    {
        parent::tearDown();

        $filesystem = new Filesystem();
        $filesystem->remove($this->tempDir);
    }

    protected function createPrefilledRepository(Resource $root)
    {
        $repo = new PathMappingRepository(new ArrayStore());
        $repo->add('/', $root);

        return $repo;
    }

    protected function createWriteRepository()
    {
        return new PathMappingRepository(new ArrayStore());
    }

    protected function createReadRepository(EditableRepository $writeRepo)
    {
        return $writeRepo;
    }

    protected function createFile($path = null, $body = TestFilesystemFile::BODY)
    {
        return new TestFilesystemFile($path, $body);
    }

    protected function createDirectory($path = null, array $children = array())
    {
        return new TestFilesystemDirectory($path, $children);
    }

    protected function buildStructure(Resource $root)
    {
        return $this->buildRecursive($root);
    }

    /**
     * @param TestFilesystemFile|TestFilesystemDirectory $resource
     * @param string                                     $parentPath
     *
     * @return DirectoryResource|FileResource
     */
    protected function buildRecursive($resource, $parentPath = '')
    {
        if ($resource instanceof TestFilesystemDirectory) {
            if ($resource->getPath() !== null) {
                $dirname = rtrim($parentPath.$resource->getPath(), '/');
            } else {
                $dirname = $parentPath.'/dir'.self::$createdDirectories;
                ++self::$createdDirectories;
            }

            if (!is_dir($this->tempDir.$dirname)) {
                mkdir($this->tempDir.$dirname, 0777, true);
            }

            foreach ($resource->listChildren() as $child) {
                $this->buildRecursive($child, $dirname);
            }

            return new DirectoryResource($this->tempDir.$dirname, $resource->getPath());
        } else {
            if ($resource->getPath() !== null) {
                $filename = rtrim($parentPath.$resource->getPath(), '/');
            } else {
                $filename = $parentPath.'/file'.self::$createdFiles;
                ++self::$createdFiles;
            }

            $dirname = dirname($this->tempDir.$filename);

            if (!is_dir($dirname)) {
                mkdir($dirname, 0777, true);
            }

            file_put_contents($this->tempDir.$filename, $resource->getBody());

            return new FileResource($this->tempDir.$filename, $resource->getPath());
        }
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
        $store = new ArrayStore();
        $store->set('/', null);
        $store->set('/webmozart', null);
        $store->set('/webmozart/foo', array(__DIR__.'/Fixtures/dir5'));

        $repository = new PathMappingRepository($store);

        // Get
        $resource = $repository->get('/webmozart/foo/sub');

        $this->assertInstanceOf('Puli\Repository\Api\Resource\FilesystemResource', $resource);
        $this->assertEquals(str_replace(DIRECTORY_SEPARATOR, '/', __DIR__).'/Fixtures/dir5/sub', $resource->getFilesystemPath());

        // Find
        $resources = $repository->find('/**/sub');

        $this->assertCount(1, $resources);

        $resource = $resources->get(0);

        $this->assertInstanceOf('Puli\Repository\Api\Resource\FilesystemResource', $resource);
        $this->assertEquals(str_replace(DIRECTORY_SEPARATOR, '/', __DIR__).'/Fixtures/dir5/sub', $resource->getFilesystemPath());
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

    public function testCreateWithFilledStore()
    {
        $store = new ArrayStore();
        $store->set('/webmozart', __DIR__.'/Fixtures/dir5');
        $store->set('/webmozart/file1', __DIR__.'/Fixtures/dir5/file1');

        $repo = new PathMappingRepository($store);

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
}
