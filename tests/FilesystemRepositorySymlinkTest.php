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

use Puli\Repository\Api\Resource\Resource;
use Puli\Repository\Api\ResourceRepository;
use Puli\Repository\FilesystemRepository;
use Puli\Repository\Resource\DirectoryResource;
use Puli\Repository\Resource\FileResource;
use Symfony\Component\Filesystem\Filesystem;

/**
 * @since  1.0
 * @author Bernhard Schussek <bschussek@gmail.com>
 */
class FilesystemRepositorySymlinkTest extends AbstractEditableRepositoryTest
{
    private $tempDir;

    protected function setUp()
    {
        if (!FilesystemRepository::isSymlinkSupported()) {
            $this->markTestSkipped('Symlinks are not supported');

            return;
        }

        while (false === mkdir($this->tempDir = sys_get_temp_dir().'/puli-repository/FilesystemRepositorySymlinkTest'.rand(10000, 99999), 0777, true)) {}

        parent::setUp();
    }

    protected function tearDown()
    {
        parent::tearDown();

        $filesystem = new Filesystem();
        $filesystem->remove($this->tempDir);
    }

    protected function createRepository(Resource $root)
    {
        $repo = new FilesystemRepository($this->tempDir, true);
        $repo->add('/', $root);

        return $repo;
    }

    protected function createEditableRepository()
    {
        return new FilesystemRepository($this->tempDir, true);
    }

    public function testAddDirectoryCreatesSymlink()
    {
        $this->repo->add('/webmozart/dir', new DirectoryResource(__DIR__.'/Fixtures/dir1'));

        $this->assertTrue(is_link($this->tempDir.'/webmozart/dir'));
        $this->assertSame(__DIR__.'/Fixtures/dir1', readlink($this->tempDir.'/webmozart/dir'));
    }

    public function testOverwriteDirectoryWithDirectoryTurnsSymlinkIntoDirectory()
    {
        $this->repo->add('/webmozart/dir', new DirectoryResource(__DIR__.'/Fixtures/dir1'));
        $this->repo->add('/webmozart/dir', new DirectoryResource(__DIR__.'/Fixtures/dir2'));

        // Symlink is turned into a copy
        $this->assertFalse(is_link($this->tempDir.'/webmozart/dir'));

        // Directories are merged
        $this->assertTrue(is_link($this->tempDir.'/webmozart/dir/file1'));
        $this->assertSame(__DIR__.'/Fixtures/dir1/file1', readlink($this->tempDir.'/webmozart/dir/file1'));
        $this->assertTrue(is_link($this->tempDir.'/webmozart/dir/file2'));
        $this->assertSame(__DIR__.'/Fixtures/dir2/file2', readlink($this->tempDir.'/webmozart/dir/file2'));
        $this->assertTrue(is_link($this->tempDir.'/webmozart/dir/file3'));
        $this->assertSame(__DIR__.'/Fixtures/dir2/file3', readlink($this->tempDir.'/webmozart/dir/file3'));
    }

    public function testOverwriteDirectoryWithDirectoryMergesSubdirectories()
    {
        $this->repo->add('/webmozart/dir', new DirectoryResource(__DIR__.'/Fixtures/dir3'));
        $this->repo->add('/webmozart/dir', new DirectoryResource(__DIR__.'/Fixtures/dir4'));

        // Symlink is turned into a copy
        $this->assertFalse(is_link($this->tempDir.'/webmozart/dir'));

        // Subdirectories are merged
        $this->assertTrue(is_dir($this->tempDir.'/webmozart/dir/sub'));
        $this->assertTrue(is_link($this->tempDir.'/webmozart/dir/sub/file1'));
        $this->assertSame(__DIR__.'/Fixtures/dir3/sub/file1', readlink($this->tempDir.'/webmozart/dir/sub/file1'));
        $this->assertTrue(is_link($this->tempDir.'/webmozart/dir/sub/file2'));
        $this->assertSame(__DIR__.'/Fixtures/dir4/sub/file2', readlink($this->tempDir.'/webmozart/dir/sub/file2'));
        $this->assertTrue(is_link($this->tempDir.'/webmozart/dir/sub/file3'));
        $this->assertSame(__DIR__.'/Fixtures/dir4/sub/file3', readlink($this->tempDir.'/webmozart/dir/sub/file3'));
    }

    public function testOverwriteDirectoryWithFileReplacesSymlink()
    {
        $this->repo->add('/webmozart/path', new DirectoryResource(__DIR__.'/Fixtures/dir1'));
        $this->repo->add('/webmozart/path', new FileResource(__DIR__.'/Fixtures/dir1/file1'));

        $this->assertTrue(is_link($this->tempDir.'/webmozart/path'));
        $this->assertSame(__DIR__.'/Fixtures/dir1/file1', readlink($this->tempDir.'/webmozart/path'));
    }

    public function testAddFileCreatesSymlink()
    {
        $this->repo->add('/webmozart/file', new FileResource(__DIR__.'/Fixtures/dir1/file2'));

        $this->assertTrue(is_link($this->tempDir.'/webmozart/file'));
        $this->assertSame(__DIR__.'/Fixtures/dir1/file2', readlink($this->tempDir.'/webmozart/file'));
    }

    public function testOverwriteFileWithDirectoryReplacesSymlink()
    {
        $this->repo->add('/webmozart/path', new FileResource(__DIR__.'/Fixtures/dir1/file2'));
        $this->repo->add('/webmozart/path', new DirectoryResource(__DIR__.'/Fixtures/dir1'));

        $this->assertTrue(is_link($this->tempDir.'/webmozart/path'));
        $this->assertSame(__DIR__.'/Fixtures/dir1', readlink($this->tempDir.'/webmozart/path'));
    }

    public function testOverwriteFileWithFileReplacesSymlink()
    {
        $this->repo->add('/webmozart/path', new FileResource(__DIR__.'/Fixtures/dir1/file2'));
        $this->repo->add('/webmozart/path', new FileResource(__DIR__.'/Fixtures/dir1/file1'));

        $this->assertTrue(is_link($this->tempDir.'/webmozart/path'));
        $this->assertSame(__DIR__.'/Fixtures/dir1/file1', readlink($this->tempDir.'/webmozart/path'));
    }
}
