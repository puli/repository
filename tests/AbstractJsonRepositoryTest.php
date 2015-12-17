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

use Puli\Repository\Api\Resource\PuliResource;
use Puli\Repository\OptimizedJsonRepository;
use Puli\Repository\JsonRepository;
use Puli\Repository\Resource\DirectoryResource;
use Puli\Repository\Resource\FileResource;
use Puli\Repository\Tests\Resource\TestFilesystemDirectory;
use Puli\Repository\Tests\Resource\TestFilesystemFile;
use Symfony\Component\Filesystem\Filesystem;
use Webmozart\KeyValueStore\Api\KeyValueStore;
use Webmozart\KeyValueStore\ArrayStore;

/**
 * @author Bernhard Schussek <bschussek@gmail.com>
 * @author Titouan Galopin <galopintitouan@gmail.com>
 */
abstract class AbstractJsonRepositoryTest extends AbstractEditableRepositoryTest
{
    /**
     * @var ArrayStore
     */
    protected $store;

    /**
     * @var OptimizedJsonRepository
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

    /**
     * Counter to avoid collisions during tests on directories.
     *
     * @var int
     */
    protected static $createdDirectories = 0;

    protected function setUp()
    {
        parent::setUp();

        $this->tempDir = __DIR__.'/Fixtures/tmp';

        $filesystem = new Filesystem();
        $filesystem->mkdir($this->tempDir);

        $this->store = new ArrayStore();
        $this->repo = $this->createBaseDirectoryRepository($this->store, __DIR__.'/Fixtures');
    }

    protected function tearDown()
    {
        parent::tearDown();

        $filesystem = new Filesystem();
        $filesystem->remove($this->tempDir);
    }

    protected function createFile($path = null, $body = TestFilesystemFile::BODY)
    {
        return new TestFilesystemFile($path, $body);
    }

    protected function createDirectory($path = null, array $children = array())
    {
        return new TestFilesystemDirectory($path, $children);
    }

    protected function buildStructure(PuliResource $root)
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

    /**
     * @param KeyValueStore $store
     * @param string        $baseDirectory
     *
     * @return JsonRepository|OptimizedJsonRepository
     */
    abstract protected function createBaseDirectoryRepository(KeyValueStore $store, $baseDirectory);

    /**
     * @expectedException \Puli\Repository\Api\UnsupportedResourceException
     */
    public function testBaseDirectoryException()
    {
        $repository = $this->createBaseDirectoryRepository($this->store, __DIR__.'/Fixtures/dir1');
        $repository->add('/webmozart/foo/bar', new FileResource(__DIR__.'/Fixtures/dir2/file2'));
    }
}
