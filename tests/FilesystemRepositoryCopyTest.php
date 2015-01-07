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
class FilesystemRepositoryCopyTest extends AbstractEditableRepositoryTest
{
    private $tempDir;

    protected function setUp()
    {
        while (false === mkdir($this->tempDir = sys_get_temp_dir().'/puli-repository/FilesystemRepositoryCopyTest'.rand(10000, 99999), 0777, true)) {}

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
        $repo = new FilesystemRepository($this->tempDir, false);
        $repo->add('/', $root);

        return $repo;
    }

    protected function createEditableRepository()
    {
        return new FilesystemRepository($this->tempDir, false);
    }

    /**
     * @expectedException \InvalidArgumentException
     */
    public function testPassNonExistingBaseDirectory()
    {
        new FilesystemRepository($this->tempDir.'/foo');
    }

    /**
     * @expectedException \InvalidArgumentException
     */
    public function testPassFileAsBaseDirectory()
    {
        touch($this->tempDir.'/file');

        new FilesystemRepository($this->tempDir.'/file');
    }

    public function testGetFileLink()
    {
        touch($this->tempDir.'/file');
        symlink($this->tempDir.'/file', $this->tempDir.'/link');

        $repo = new FilesystemRepository($this->tempDir);

        $expected = new FileResource($this->tempDir.'/link', '/link');
        $expected->attachTo($repo);

        $this->assertEquals($expected, $repo->get('/link'));
    }

    public function testGetDirectoryLink()
    {
        mkdir($this->tempDir.'/dir');
        symlink($this->tempDir.'/dir', $this->tempDir.'/link');

        $repo = new FilesystemRepository($this->tempDir);

        $expected = new DirectoryResource($this->tempDir.'/link', '/link');
        $expected->attachTo($repo);

        $this->assertEquals($expected, $repo->get('/link'));
    }

    /**
     * @expectedException \Puli\Repository\Api\UnsupportedLanguageException
     * @expectedExceptionMessage foobar
     */
    public function testContainsFailsIfLanguageNotGlob()
    {
        $repo = new FilesystemRepository($this->tempDir);

        $repo->contains('/*', 'foobar');
    }

    /**
     * @expectedException \Puli\Repository\Api\UnsupportedLanguageException
     * @expectedExceptionMessage foobar
     */
    public function testFindFailsIfLanguageNotGlob()
    {
        $repo = new FilesystemRepository($this->tempDir);

        $repo->find('/*', 'foobar');
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
