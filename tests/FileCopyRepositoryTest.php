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

use Puli\Repository\Api\Resource\DirectoryResource;
use Puli\Repository\Api\ResourceRepository;
use Puli\Repository\FileCopyRepository;
use Puli\Repository\Resource\LocalDirectoryResource;
use Puli\Repository\Resource\LocalFileResource;
use Puli\Repository\Tests\Resource\TestFile;
use Symfony\Component\Filesystem\Filesystem;
use Webmozart\KeyValueStore\ArrayStore;

/**
 * @since  1.0
 * @author Bernhard Schussek <bschussek@gmail.com>
 */
class FileCopyRepositoryTest extends AbstractManageableRepositoryTest
{
    private $tempDir;

    protected function setUp()
    {
        while (false === mkdir($this->tempDir = sys_get_temp_dir().'/puli-repository/FileCopyRepositoryTest'.rand(10000, 99999), 0777, true)) {}

        parent::setUp();
    }

    protected function tearDown()
    {
        $filesystem = new Filesystem();
        $filesystem->remove($this->tempDir);

        parent::tearDown();
    }

    protected function createRepository(DirectoryResource $root)
    {
        $repo = new FileCopyRepository($this->tempDir, new ArrayStore());
        $repo->add('/', $root);

        return $repo;
    }

    protected function createManageableRepository(ResourceRepository $backend = null)
    {
        return new FileCopyRepository($this->tempDir, new ArrayStore(), $backend);
    }

    public function testBaseDirectoryCreatedIfNonExisting()
    {
        new FileCopyRepository($this->tempDir.'/foo', new ArrayStore());

        $this->assertFileExists($this->tempDir.'/foo');
    }

    /**
     * @expectedException \InvalidArgumentException
     */
    public function testPassFileAsBaseDirectory()
    {
        touch($this->tempDir.'/file');

        new FileCopyRepository($this->tempDir.'/file', new ArrayStore());
    }

    public function testGetOverriddenFile()
    {
        // Not supported
        $this->pass();
    }

    public function testGetOverriddenDirectory()
    {
        // Not supported
        $this->pass();
    }

    /**
     * @expectedException \Puli\Repository\Api\UnsupportedResourceException
     */
    public function testFailIfAddedResourceNeitherFileNorDirectory()
    {
        $this->repo->add('/webmozart', $this->getMock('Puli\Repository\Api\Resource\Resource'));
    }

    public function testAddLocalDirectory()
    {
        $this->repo->add('/webmozart/dir', new LocalDirectoryResource(__DIR__.'/Fixtures/dir1'));

        $dir = $this->repo->get('/webmozart/dir');
        $file1 = $this->repo->get('/webmozart/dir/file1');
        $file2 = $this->repo->get('/webmozart/dir/file2');

        $this->assertInstanceOf('Puli\Repository\Api\Resource\DirectoryResource', $dir);
        $this->assertInstanceOf('Puli\Repository\Api\Resource\FileResource', $file1);
        $this->assertInstanceOf('Puli\Repository\Api\Resource\FileResource', $file2);
    }

    public function testAddLocalFile()
    {
        $this->repo->add('/webmozart/file', new LocalFileResource(__DIR__.'/Fixtures/dir1/file2'));

        $file = $this->repo->get('/webmozart/file');

        $this->assertInstanceOf('Puli\Repository\Api\Resource\FileResource', $file);
    }

    public function testAddOverridesPreviousVersion()
    {
        $this->repo->add('/webmozart/puli/file', new LocalFileResource(__DIR__.'/Fixtures/dir1/file1'));
        $this->repo->add('/webmozart/puli/file', new LocalFileResource(__DIR__.'/Fixtures/dir1/file2'));

        $file = $this->repo->get('/webmozart/puli/file');

        $this->assertInstanceOf('Puli\Repository\Api\Resource\FileResource', $file);
        $this->assertSame('/webmozart/puli/file', $file->getPath());
        $this->assertSame($this->tempDir.'/webmozart/puli/file', $file->getLocalPath());
        $this->assertSame($this->repo, $file->getRepository());
        $this->assertSame(2, $file->getVersion());

        $file = $this->repo->get('/webmozart/puli/file', 1);

        $this->assertInstanceOf('Puli\Repository\Api\Resource\FileResource', $file);
        $this->assertSame('/webmozart/puli/file', $file->getPath());
        $this->assertSame(__DIR__.'/Fixtures/dir1/file1', $file->getLocalPath());
        $this->assertSame($this->repo, $file->getRepository());
        $this->assertSame(1, $file->getVersion());
    }

    public function testAddResetsVersionForNonLocalResource()
    {
        $this->repo->add('/webmozart/puli/file', new LocalFileResource(__DIR__.'/Fixtures/dir1/file1'));

        // Versioning is not supported for non-local resources
        // The version is reset to 1
        $this->repo->add('/webmozart/puli/file', new TestFile());

        $file = $this->repo->get('/webmozart/puli/file');

        $this->assertInstanceOf('Puli\Repository\Api\Resource\FileResource', $file);
        $this->assertSame('/webmozart/puli/file', $file->getPath());
        $this->assertSame($this->repo, $file->getRepository());
        $this->assertSame(1, $file->getVersion());

        $fileAtVersion1 = $this->repo->get('/webmozart/puli/file', 1);

        $this->assertEquals($file, $fileAtVersion1);
    }
}
