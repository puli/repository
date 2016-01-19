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

use Puli\Repository\Resource\DirectoryResource;
use Symfony\Component\Filesystem\Filesystem;
use Webmozart\Glob\Test\TestUtil;

/**
 * @author Titouan Galopin <galopintitouan@gmail.com>
 */
abstract class AbstractFilesystemRepositorySymlinkTest extends AbstractFilesystemRepositoryTest
{
    protected $tempBaseDir;

    protected $tempDir;

    /**
     * Copy fixtures to temporary directory to prevent messing up the real
     * fixtures when symlinks do not work.
     */
    protected $tempFixtures;

    protected function setUp()
    {
        $this->markAsSkippedIfSymlinkIsMissing();

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

    public function testClearDirectoryLinksDoesNotRemoveChildrenFiles()
    {
        $this->writeRepo->add('/webmozart', new DirectoryResource($this->tempFixtures.'/dir2'));

        $this->assertTrue($this->writeRepo->contains('/webmozart/file2'));
        $this->assertTrue($this->writeRepo->contains('/webmozart/file3'));

        $this->writeRepo->clear();

        // We should not be able to access the resources
        $this->assertFalse($this->writeRepo->contains('/webmozart/file2'));
        $this->assertFalse($this->writeRepo->contains('/webmozart/file3'));

        // But the files should still be here
        $this->assertFileExists($this->tempFixtures.'/dir2/file2');
        $this->assertFileExists($this->tempFixtures.'/dir2/file3');
    }
}
