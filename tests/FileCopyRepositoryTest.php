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
use Puli\Repository\FileCopyRepository;
use Puli\Repository\Resource\DirectoryResource;
use Puli\Repository\Resource\FileResource;
use Symfony\Component\Filesystem\Filesystem;

/**
 * @since  1.0
 * @author Bernhard Schussek <bschussek@gmail.com>
 */
class FileCopyRepositoryTest extends AbstractEditableRepositoryTest
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

    protected function createRepository(Resource $root)
    {
        $repo = new FileCopyRepository($this->tempDir);
        $repo->add('/', $root);

        return $repo;
    }

    protected function createEditableRepository(ResourceRepository $backend = null)
    {
        return new FileCopyRepository($this->tempDir, $backend);
    }

    public function testBaseDirectoryCreatedIfNonExisting()
    {
        new FileCopyRepository($this->tempDir.'/foo');

        $this->assertFileExists($this->tempDir.'/foo');
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
     * @expectedException \Puli\Repository\Api\UnsupportedResourceException
     */
    public function testFailIfAddedResourceHasBodyAndChildren()
    {
        $resource = $this->getMock('Puli\Repository\Api\Resource\BodyResource');

        $resource->expects($this->any())
            ->method('hasChildren')
            ->will($this->returnValue(true));

        $this->repo->add('/webmozart', $resource);
    }

    public function testAddDirectory()
    {
        $this->repo->add('/webmozart/dir', new DirectoryResource(__DIR__.'/Fixtures/dir1'));

        $dir = $this->repo->get('/webmozart/dir');
        $file1 = $this->repo->get('/webmozart/dir/file1');
        $file2 = $this->repo->get('/webmozart/dir/file2');

        $this->assertInstanceOf('Puli\Repository\Resource\DirectoryResource', $dir);
        $this->assertInstanceOf('Puli\Repository\Resource\FileResource', $file1);
        $this->assertInstanceOf('Puli\Repository\Resource\FileResource', $file2);
    }

    public function testAddFile()
    {
        $this->repo->add('/webmozart/file', new FileResource(__DIR__.'/Fixtures/dir1/file2'));

        $file = $this->repo->get('/webmozart/file');

        $this->assertInstanceOf('Puli\Repository\Resource\FileResource', $file);
    }

    /**
     * @expectedException \Puli\Repository\NoDirectoryException
     */
    public function testFailIfAddingFileAsChildOfFile()
    {
        $this->repo->add('/webmozart/puli', new FileResource(__DIR__.'/Fixtures/dir1/file1'));
        $this->repo->add('/webmozart/puli/file', new FileResource(__DIR__.'/Fixtures/dir1/file2'));
    }

    /**
     * @expectedException \Puli\Repository\Api\UnsupportedLanguageException
     * @expectedExceptionMessage foobar
     */
    public function testRemoveFailsIfLanguageNotGlob()
    {
        $this->repo->remove('/*', 'foobar');
    }
}
