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

use Puli\Repository\FileCopyRepository;
use Puli\Repository\Filesystem\Resource\LocalDirectoryResource;
use Puli\Repository\Filesystem\Resource\LocalFileResource;
use Puli\Repository\ManageableRepository;
use Puli\Repository\Resource\DirectoryResource;
use Puli\Repository\ResourceRepository;
use Symfony\Component\Filesystem\Filesystem;

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
        $repo = new FileCopyRepository($this->tempDir);
        $repo->add('/', $root);

        return $repo;
    }

    protected function createManageableRepository(ResourceRepository $backend = null)
    {
        return new FileCopyRepository($this->tempDir, $backend);
    }

    /**
     * @expectedException \InvalidArgumentException
     */
    public function testPassNonExistingBaseDirectory()
    {
        new FileCopyRepository($this->tempDir.'/foo');
    }

    /**
     * @expectedException \InvalidArgumentException
     */
    public function testPassFileAsBaseDirectory()
    {
        touch($this->tempDir.'/file');

        new FileCopyRepository($this->tempDir.'/file');
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
     * @expectedException \Puli\Repository\UnsupportedResourceException
     */
    public function testFailIfAddedResourceNeitherFileNorDirectory()
    {
        $this->repo->add('/webmozart', $this->getMock('Puli\Repository\Resource\Resource'));
    }

    public function testAddLocalDirectory()
    {
        $this->repo->add('/webmozart/dir', new LocalDirectoryResource(__DIR__.'/Fixtures/dir1'));

        $dir = $this->repo->get('/webmozart/dir');
        $file1 = $this->repo->get('/webmozart/dir/file1');
        $file2 = $this->repo->get('/webmozart/dir/file2');

        $this->assertInstanceOf('Puli\Repository\Resource\DirectoryResource', $dir);
        $this->assertInstanceOf('Puli\Repository\Resource\FileResource', $file1);
        $this->assertInstanceOf('Puli\Repository\Resource\FileResource', $file2);
    }

    public function testAddLocalFile()
    {
        $this->repo->add('/webmozart/file', new LocalFileResource(__DIR__.'/Fixtures/dir1/file2'));

        $file = $this->repo->get('/webmozart/file');

        $this->assertInstanceOf('Puli\Repository\Resource\FileResource', $file);
    }
}
