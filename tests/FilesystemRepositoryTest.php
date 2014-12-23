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

use Puli\Repository\FilesystemRepository;
use Puli\Repository\Resource\DirectoryResource;
use Puli\Repository\Resource\Iterator\RecursiveResourceIteratorIterator;
use Puli\Repository\Resource\Iterator\ResourceCollectionIterator;
use Puli\Repository\Resource\LocalDirectoryResource;
use Puli\Repository\Resource\LocalFileResource;
use Puli\Repository\ResourceRepository;
use Symfony\Component\Filesystem\Filesystem;

/**
 * @since  1.0
 * @author Bernhard Schussek <bschussek@gmail.com>
 */
class FilesystemRepositoryTest extends AbstractRepositoryTest
{
    private $baseDir;

    protected function setUp()
    {
        while (false === mkdir($this->baseDir = sys_get_temp_dir().'/puli-repository/FilesystemRepositoryTest'.rand(10000, 99999), 0777, true)) {}

        parent::setUp();
    }

    protected function tearDown()
    {
        parent::tearDown();

        $filesystem = new Filesystem();
        $filesystem->remove($this->baseDir);
    }

    /**
     * @param DirectoryResource $root
     *
     * @return ResourceRepository
     */
    protected function createRepository(DirectoryResource $root)
    {
        $filesystem = new Filesystem();
        $iterator = new RecursiveResourceIteratorIterator(
            new ResourceCollectionIterator($root->listEntries()),
            RecursiveResourceIteratorIterator::SELF_FIRST
        );

        foreach ($iterator as $resource) {
            if ($resource instanceof DirectoryResource) {
                $filesystem->mkdir($this->baseDir.$resource->getPath());
            } else {
                file_put_contents($this->baseDir.$resource->getPath(), $resource->getContents());
            }
        }

        return new FilesystemRepository($this->baseDir);
    }

    /**
     * @expectedException \InvalidArgumentException
     */
    public function testPassNonExistingBaseDirectory()
    {
        new FilesystemRepository($this->baseDir.'/foo');
    }

    /**
     * @expectedException \InvalidArgumentException
     */
    public function testPassFileAsBaseDirectory()
    {
        touch($this->baseDir.'/file');

        new FilesystemRepository($this->baseDir.'/file');
    }

    public function testGetFileLink()
    {
        touch($this->baseDir.'/file');
        symlink($this->baseDir.'/file', $this->baseDir.'/link');

        $repo = new FilesystemRepository($this->baseDir);

        $expected = new LocalFileResource($this->baseDir.'/link', '/link');
        $expected->attachTo($repo);

        $this->assertEquals($expected, $repo->get('/link'));
    }

    public function testGetDirectoryLink()
    {
        mkdir($this->baseDir.'/dir');
        symlink($this->baseDir.'/dir', $this->baseDir.'/link');

        $repo = new FilesystemRepository($this->baseDir);

        $expected = new LocalDirectoryResource($this->baseDir.'/link', '/link');
        $expected->attachTo($repo);

        $this->assertEquals($expected, $repo->get('/link'));
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
}
