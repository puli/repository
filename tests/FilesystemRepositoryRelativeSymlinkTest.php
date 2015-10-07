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
use Puli\Repository\Api\Resource\PuliResource;
use Puli\Repository\FilesystemRepository;
use Puli\Repository\Resource\DirectoryResource;
use Puli\Repository\Resource\FileResource;
use Symfony\Component\Filesystem\Filesystem;
use Webmozart\Glob\Test\TestUtil;

/**
 * @author Bernhard Schussek <bschussek@gmail.com>
 */
class FilesystemRepositoryRelativeSymlinkTest extends AbstractEditableRepositoryTest
{
    private $tempBaseDir;

    private $tempDir;

    /**
     * Copy fixtures to temporary directory to prevent messing up the real
     * fixtures when symlinks do not work.
     */
    private $tempFixtures;

    protected function setUp()
    {
        if (defined('PHP_WINDOWS_VERSION_MAJOR')) {
            $this->markTestSkipped('Relative symbolic links are not supported on Windows.');
        }

        $this->tempBaseDir = TestUtil::makeTempDir('puli-repository', __CLASS__);

        // Create both directories in the same directory, so that relative links
        // work from one to the other
        $this->tempDir = $this->tempBaseDir.'/workspace';
        $this->tempFixtures = $this->tempBaseDir.'/fixtures';

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
        $filesystem->remove($this->tempBaseDir);
    }

    protected function createPrefilledRepository(PuliResource $root)
    {
        $repo = new FilesystemRepository($this->tempDir, true, true);
        $repo->add('/', $root);

        return $repo;
    }

    protected function createWriteRepository()
    {
        return new FilesystemRepository($this->tempDir, true, true);
    }

    protected function createReadRepository(EditableRepository $writeRepo)
    {
        return new FilesystemRepository($this->tempDir, true, false);
    }

    public function testAddDirectoryCreatesSymlink()
    {
        $this->writeRepo->add('/webmozart/dir', new DirectoryResource($this->tempFixtures.'/dir1'));

        $this->assertTrue(is_link($this->tempDir.'/webmozart/dir'));
        $this->assertSame('../../fixtures/dir1', readlink($this->tempDir.'/webmozart/dir'));
    }

    public function testOverwriteDirectoryWithDirectoryTurnsSymlinkIntoDirectory()
    {
        $this->writeRepo->add('/webmozart/dir', new DirectoryResource($this->tempFixtures.'/dir1'));
        $this->writeRepo->add('/webmozart/dir', new DirectoryResource($this->tempFixtures.'/dir2'));

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
        $this->writeRepo->add('/webmozart/dir', new DirectoryResource($this->tempFixtures.'/dir3'));
        $this->writeRepo->add('/webmozart/dir', new DirectoryResource($this->tempFixtures.'/dir4'));

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
        $this->writeRepo->add('/webmozart/path', new DirectoryResource($this->tempFixtures.'/dir1'));
        $this->writeRepo->add('/webmozart/path', new FileResource($this->tempFixtures.'/dir1/file1'));

        $this->assertTrue(is_link($this->tempDir.'/webmozart/path'));
        $this->assertSame('../../fixtures/dir1/file1', readlink($this->tempDir.'/webmozart/path'));
    }

    public function testAddFileCreatesSymlink()
    {
        $this->writeRepo->add('/webmozart/file', new FileResource($this->tempFixtures.'/dir1/file2'));

        $this->assertTrue(is_link($this->tempDir.'/webmozart/file'));
        $this->assertSame('../../fixtures/dir1/file2', readlink($this->tempDir.'/webmozart/file'));
    }

    public function testOverwriteFileWithDirectoryReplacesSymlink()
    {
        $this->writeRepo->add('/webmozart/path', new FileResource($this->tempFixtures.'/dir1/file2'));
        $this->writeRepo->add('/webmozart/path', new DirectoryResource($this->tempFixtures.'/dir1'));

        $this->assertTrue(is_link($this->tempDir.'/webmozart/path'));
        $this->assertSame('../../fixtures/dir1', readlink($this->tempDir.'/webmozart/path'));
    }

    public function testOverwriteFileWithFileReplacesSymlink()
    {
        $this->writeRepo->add('/webmozart/path', new FileResource($this->tempFixtures.'/dir1/file2'));
        $this->writeRepo->add('/webmozart/path', new FileResource($this->tempFixtures.'/dir1/file1'));

        $this->assertTrue(is_link($this->tempDir.'/webmozart/path'));
        $this->assertSame('../../fixtures/dir1/file1', readlink($this->tempDir.'/webmozart/path'));
    }

    public function testAddSubDirectoryTurnsParentSymlinkIntoDirectory()
    {
        $this->writeRepo->add('/webmozart', new DirectoryResource($this->tempFixtures.'/dir1'));
        $this->writeRepo->add('/webmozart/dir', new DirectoryResource($this->tempFixtures.'/dir2'));

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
        $this->writeRepo->add('/webmozart', new DirectoryResource($this->tempFixtures.'/dir1'));
        $this->writeRepo->add('/webmozart/file3', new FileResource($this->tempFixtures.'/dir2/file3'));

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
        $this->writeRepo->add('/webmozart', new DirectoryResource($this->tempFixtures.'/dir1'));
        $this->writeRepo->add('/webmozart/file3', $this->createFile(null, 'some body'));

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

    public function testAddSubSubDirectoryTurnsParentSymlinkIntoDirectory()
    {
        $this->writeRepo->add('/webmozart', new DirectoryResource($this->tempFixtures.'/dir3'));
        $this->writeRepo->add('/webmozart/sub/dir', new DirectoryResource($this->tempFixtures.'/dir1'));

        // Symlink is turned into a copy
        $this->assertFalse(is_link($this->tempDir.'/webmozart'));

        // Directories are merged
        $this->assertTrue(is_link($this->tempDir.'/webmozart/sub/file1'));
        $this->assertSame('../../../fixtures/dir3/sub/file1', readlink($this->tempDir.'/webmozart/sub/file1'));
        $this->assertTrue(is_link($this->tempDir.'/webmozart/sub/file2'));
        $this->assertSame('../../../fixtures/dir3/sub/file2', readlink($this->tempDir.'/webmozart/sub/file2'));
        $this->assertTrue(is_link($this->tempDir.'/webmozart/sub/dir'));
        $this->assertSame('../../../fixtures/dir1', readlink($this->tempDir.'/webmozart/sub/dir'));
    }

    public function testAddSubSubFileTurnsParentSymlinkIntoDirectory()
    {
        $this->writeRepo->add('/webmozart', new DirectoryResource($this->tempFixtures.'/dir3'));
        $this->writeRepo->add('/webmozart/sub/file3', new FileResource($this->tempFixtures.'/dir2/file3'));

        // Symlink is turned into a copy
        $this->assertFalse(is_link($this->tempDir.'/webmozart'));

        // Directories are merged
        $this->assertTrue(is_link($this->tempDir.'/webmozart/sub/file1'));
        $this->assertSame('../../../fixtures/dir3/sub/file1', readlink($this->tempDir.'/webmozart/sub/file1'));
        $this->assertTrue(is_link($this->tempDir.'/webmozart/sub/file2'));
        $this->assertSame('../../../fixtures/dir3/sub/file2', readlink($this->tempDir.'/webmozart/sub/file2'));
        $this->assertTrue(is_link($this->tempDir.'/webmozart/sub/file3'));
        $this->assertSame('../../../fixtures/dir2/file3', readlink($this->tempDir.'/webmozart/sub/file3'));
    }

    public function testAddSubSubResourceWithBodyTurnsParentSymlinkIntoDirectory()
    {
        $this->writeRepo->add('/webmozart', new DirectoryResource($this->tempFixtures.'/dir3'));
        $this->writeRepo->add('/webmozart/sub/file3', $this->createFile(null, 'some body'));

        // Symlink is turned into a copy
        $this->assertFalse(is_link($this->tempDir.'/webmozart'));

        // Directories are merged
        $this->assertTrue(is_link($this->tempDir.'/webmozart/sub/file1'));
        $this->assertSame('../../../fixtures/dir3/sub/file1', readlink($this->tempDir.'/webmozart/sub/file1'));
        $this->assertTrue(is_link($this->tempDir.'/webmozart/sub/file2'));
        $this->assertSame('../../../fixtures/dir3/sub/file2', readlink($this->tempDir.'/webmozart/sub/file2'));
        $this->assertFalse(is_link($this->tempDir.'/webmozart/sub/file3'));
        $this->assertSame('some body', file_get_contents($this->tempDir.'/webmozart/sub/file3'));
    }
}
