<?php

/*
 * This file is part of the puli/repository package.
 *
 * (c) Bernhard Schussek <bschussek@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Puli\Repository\Tests\Resource;

use Puli\Repository\Api\Resource\FilesystemResource;

/**
 * @since  1.0
 * @author Bernhard Schussek <bschussek@gmail.com>
 */
abstract class AbstractFilesystemResourceTest extends AbstractResourceTest
{
    private $fixturesDir;

    protected function setUp()
    {
        parent::setUp();

        $this->fixturesDir = realpath(__DIR__.'/Fixtures');
    }

    /**
     * @param string|null $path
     *
     * @return Resource
     */
    protected function createResource($path = null)
    {
        return $this->createFilesystemResource($this->getValidFilesystemPath(), $path);
    }

    /**
     * @param string      $filesystemPath
     * @param string|null $path
     *
     * @return FilesystemResource
     */
    abstract protected function createFilesystemResource($filesystemPath, $path = null);

    abstract protected function getValidFilesystemPath();

    abstract protected function getValidFilesystemPath2();

    abstract protected function getValidFilesystemPath3();

    abstract public function getInvalidFilesystemPaths();

    /**
     * @dataProvider getInvalidFilesystemPaths
     * @expectedException \InvalidArgumentException
     */
    public function testFailIfNonExistingFile($filesystemPath)
    {
        $this->createFilesystemResource($filesystemPath);
    }

    public function testGetFilesystemPath()
    {
        $filesystemPath = $this->getValidFilesystemPath();
        $resource = $this->createFilesystemResource($filesystemPath);

        $this->assertSame($filesystemPath, $resource->getFilesystemPath());
    }

    public function testAttachDoesNotChangeFilesystemPath()
    {
        $filesystemPath = $this->getValidFilesystemPath();
        $resource = $this->createFilesystemResource($filesystemPath);
        $resource->attachTo($this->repo);

        $this->assertSame($filesystemPath, $resource->getFilesystemPath());
    }

    public function testDetachDoesNotChangeFilesystemPath()
    {
        $filesystemPath = $this->getValidFilesystemPath();
        $resource = $this->createFilesystemResource($filesystemPath);
        $resource->attachTo($this->repo);
        $resource->detach($this->repo);

        $this->assertSame($filesystemPath, $resource->getFilesystemPath());
    }

    public function testSerializeKeepsFilesystemPath()
    {
        $filesystemPath = $this->getValidFilesystemPath();
        $resource = $this->createFilesystemResource($filesystemPath);

        $deserialized = unserialize(serialize($resource));

        $this->assertSame($filesystemPath, $deserialized->getFilesystemPath());
    }
}
