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
use Puli\Repository\FilesystemRepository;
use Puli\Repository\Resource\DirectoryResource;
use Puli\Repository\Resource\FileResource;
use Puli\Repository\Tests\Resource\TestFile;
use Symfony\Component\Filesystem\Filesystem;

/**
 * @since  1.0
 * @author Bernhard Schussek <bschussek@gmail.com>
 */
class FilesystemRepositoryRelativeSymlinkTest extends AbstractEditableRepositoryTest
{
    private $tempDir;

    /**
     * Copy fixtures to temporary directory to prevent messing up the real
     * fixtures when symlinks do not work.
     */
    private $tempFixtures;

    protected function setUp()
    {
        if (!FilesystemRepository::isSymlinkSupported()) {
            $this->markTestSkipped('Symlinks are not supported');

            return;
        }

        while (false === mkdir($tempDir = sys_get_temp_dir().'/puli-repository/FilesystemRepositoryRelativeSymlinkTest'.rand(10000, 99999), 0777, true)) {}

        // Create both directories in the same directory, so that relative links
        // work from one to the other
        $this->tempDir = $tempDir.'/workspace';
        $this->tempFixtures = $tempDir.'/fixtures';

        mkdir($this->tempDir);
        mkdir($this->tempFixtures);

        $filesystem = new Filesystem();
        $filesystem->mirror(__DIR__.'/Fixtures', $this->tempFixtures);

        parent::setUp();
    }

    protected function tearDown()
    {
        parent::tearDown();

        $filesystem = new Filesystem();
        $filesystem->remove($this->tempDir);
        $filesystem->remove($this->tempFixtures);
    }

    protected function createRepository(Resource $root)
    {
        $repo = new FilesystemRepository($this->tempDir, true, true);
        $repo->add('/', $root);

        return $repo;
    }

    protected function createEditableRepository()
    {
        return new FilesystemRepository($this->tempDir, true, true);
    }

    public function testAddDirectoryCreatesSymlink()
    {
        $this->repo->add('/webmozart/dir', new DirectoryResource($this->tempFixtures.'/dir1'));

        $this->assertTrue(is_link($this->tempDir.'/webmozart/dir'));
        $this->assertSame('../../fixtures/dir1', readlink($this->tempDir.'/webmozart/dir'));
    }

    public function testOverwriteDirectoryWithDirectoryTurnsSymlinkIntoDirectory()
    {
        $this->repo->add('/webmozart/dir', new DirectoryResource($this->tempFixtures.'/dir1'));
        $this->repo->add('/webmozart/dir', new DirectoryResource($this->tempFixtures.'/dir2'));

        // Symlink is turned into a copy
        $this->assertFalse(is_link($this->tempDir.'/webmozart/dir'));

        // Directories are merged
        $this->assertTrue(is_link($this->tempDir.'/webmozart/dir/file1'));
        $this->assertSame('../../../fixtures/dir1/file1', readlink($this->tempDir.'/webmozart/dir/file1'));
        $this->assertTrue(is_link($this->tempDir.'/webmozart/dir/file2'));
        $this->assertSame('../../../fixtures/dir2/file2', readlink($this->tempDir.'/webmozart/dir/file2'));
        $this->assertTrue(is_link($this->tempDir.'/webmozart/dir/file3'));
        $this->assertSame('../../../fixtures/dir2/file3', readlink($this->tempDir.'/webmozart/dir/file3'));
    }

    public function testOverwriteDirectoryWithDirectoryMergesSubdirectories()
    {
        $this->repo->add('/webmozart/dir', new DirectoryResource($this->tempFixtures.'/dir3'));
        $this->repo->add('/webmozart/dir', new DirectoryResource($this->tempFixtures.'/dir4'));

        // Symlink is turned into a copy
        $this->assertFalse(is_link($this->tempDir.'/webmozart/dir'));

        // Subdirectories are merged
        $this->assertTrue(is_dir($this->tempDir.'/webmozart/dir/sub'));
        $this->assertTrue(is_link($this->tempDir.'/webmozart/dir/sub/file1'));
        $this->assertSame('../../../../fixtures/dir3/sub/file1', readlink($this->tempDir.'/webmozart/dir/sub/file1'));
        $this->assertTrue(is_link($this->tempDir.'/webmozart/dir/sub/file2'));
        $this->assertSame('../../../../fixtures/dir4/sub/file2', readlink($this->tempDir.'/webmozart/dir/sub/file2'));
        $this->assertTrue(is_link($this->tempDir.'/webmozart/dir/sub/file3'));
        $this->assertSame('../../../../fixtures/dir4/sub/file3', readlink($this->tempDir.'/webmozart/dir/sub/file3'));
    }

    public function testOverwriteDirectoryWithFileReplacesSymlink()
    {
        $this->repo->add('/webmozart/path', new DirectoryResource($this->tempFixtures.'/dir1'));
        $this->repo->add('/webmozart/path', new FileResource($this->tempFixtures.'/dir1/file1'));

        $this->assertTrue(is_link($this->tempDir.'/webmozart/path'));
        $this->assertSame('../../fixtures/dir1/file1', readlink($this->tempDir.'/webmozart/path'));
    }

    public function testAddFileCreatesSymlink()
    {
        $this->repo->add('/webmozart/file', new FileResource($this->tempFixtures.'/dir1/file2'));

        $this->assertTrue(is_link($this->tempDir.'/webmozart/file'));
        $this->assertSame('../../fixtures/dir1/file2', readlink($this->tempDir.'/webmozart/file'));
    }

    public function testOverwriteFileWithDirectoryReplacesSymlink()
    {
        $this->repo->add('/webmozart/path', new FileResource($this->tempFixtures.'/dir1/file2'));
        $this->repo->add('/webmozart/path', new DirectoryResource($this->tempFixtures.'/dir1'));

        $this->assertTrue(is_link($this->tempDir.'/webmozart/path'));
        $this->assertSame('../../fixtures/dir1', readlink($this->tempDir.'/webmozart/path'));
    }

    public function testOverwriteFileWithFileReplacesSymlink()
    {
        $this->repo->add('/webmozart/path', new FileResource($this->tempFixtures.'/dir1/file2'));
        $this->repo->add('/webmozart/path', new FileResource($this->tempFixtures.'/dir1/file1'));

        $this->assertTrue(is_link($this->tempDir.'/webmozart/path'));
        $this->assertSame('../../fixtures/dir1/file1', readlink($this->tempDir.'/webmozart/path'));
    }

    public function testAddSubDirectoryTurnsParentSymlinkIntoDirectory()
    {
        $this->repo->add('/webmozart', new DirectoryResource($this->tempFixtures.'/dir1'));
        $this->repo->add('/webmozart/dir', new DirectoryResource($this->tempFixtures.'/dir2'));

        // Symlink is turned into a copy
        $this->assertFalse(is_link($this->tempDir.'/webmozart'));

        // Directories are merged
        $this->assertTrue(is_link($this->tempDir.'/webmozart/file1'));
        $this->assertSame('../../fixtures/dir1/file1', readlink($this->tempDir.'/webmozart/file1'));
        $this->assertTrue(is_link($this->tempDir.'/webmozart/file2'));
        $this->assertSame('../../fixtures/dir1/file2', readlink($this->tempDir.'/webmozart/file2'));
        $this->assertTrue(is_link($this->tempDir.'/webmozart/dir'));
        $this->assertSame('../../fixtures/dir2', readlink($this->tempDir.'/webmozart/dir'));
    }

    public function testAddSubFileTurnsParentSymlinkIntoDirectory()
    {
        $this->repo->add('/webmozart', new DirectoryResource($this->tempFixtures.'/dir1'));
        $this->repo->add('/webmozart/file3', new FileResource($this->tempFixtures.'/dir2/file3'));

        // Symlink is turned into a copy
        $this->assertFalse(is_link($this->tempDir.'/webmozart'));

        // Directories are merged
        $this->assertTrue(is_link($this->tempDir.'/webmozart/file1'));
        $this->assertSame('../../fixtures/dir1/file1', readlink($this->tempDir.'/webmozart/file1'));
        $this->assertTrue(is_link($this->tempDir.'/webmozart/file2'));
        $this->assertSame('../../fixtures/dir1/file2', readlink($this->tempDir.'/webmozart/file2'));
        $this->assertTrue(is_link($this->tempDir.'/webmozart/file3'));
        $this->assertSame('../../fixtures/dir2/file3', readlink($this->tempDir.'/webmozart/file3'));
    }

    public function testAddSubResourceWithBodyTurnsParentSymlinkIntoDirectory()
    {
        $this->repo->add('/webmozart', new DirectoryResource($this->tempFixtures.'/dir1'));
        $this->repo->add('/webmozart/file3', new TestFile(null, 'some body'));

        // Symlink is turned into a copy
        $this->assertFalse(is_link($this->tempDir.'/webmozart'));

        // Directories are merged
        $this->assertTrue(is_link($this->tempDir.'/webmozart/file1'));
        $this->assertSame('../../fixtures/dir1/file1', readlink($this->tempDir.'/webmozart/file1'));
        $this->assertTrue(is_link($this->tempDir.'/webmozart/file2'));
        $this->assertSame('../../fixtures/dir1/file2', readlink($this->tempDir.'/webmozart/file2'));
        $this->assertFalse(is_link($this->tempDir.'/webmozart/file3'));
        $this->assertSame('some body', file_get_contents($this->tempDir.'/webmozart/file3'));
    }
}
