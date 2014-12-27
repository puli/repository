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

use Puli\Repository\Resource\LocalResource;
use Puli\Repository\Resource\OverriddenPathLoader;

/**
 * @since  1.0
 * @author Bernhard Schussek <bschussek@gmail.com>
 */
abstract class AbstractLocalResourceTest extends AbstractResourceTest
{
    private $fixturesDir;

    protected function setUp()
    {
        parent::setUp();

        $this->fixturesDir = realpath(__DIR__.'/Fixtures');
    }

    /**
     * @param string|null $path
     * @param int         $version
     *
     * @return Resource
     */
    protected function createResource($path = null, $version = 1)
    {
        return $this->createLocalResource($this->getValidLocalPath(), $path, $version);
    }

    /**
     * @param string      $localPath
     * @param string|null $path
     * @param int         $version
     *
     * @return LocalResource
     */
    abstract protected function createLocalResource($localPath, $path = null, $version = 1);

    abstract protected function getValidLocalPath();

    abstract protected function getValidLocalPath2();

    abstract protected function getValidLocalPath3();

    abstract public function getInvalidLocalPaths();

    /**
     * @dataProvider getInvalidLocalPaths
     * @expectedException \InvalidArgumentException
     */
    public function testFailIfNonExistingFile($localPath)
    {
        $this->createLocalResource($localPath);
    }

    public function testGetLocalPath()
    {
        $localPath = $this->getValidLocalPath();
        $resource = $this->createLocalResource($localPath);

        $this->assertSame($localPath, $resource->getLocalPath());
    }

    public function testAttachDoesNotChangeLocalPath()
    {
        $localPath = $this->getValidLocalPath();
        $resource = $this->createLocalResource($localPath);
        $resource->attachTo($this->repo);

        $this->assertSame($localPath, $resource->getLocalPath());
    }

    public function testDetachDoesNotChangeLocalPath()
    {
        $localPath = $this->getValidLocalPath();
        $resource = $this->createLocalResource($localPath);
        $resource->attachTo($this->repo);
        $resource->detach($this->repo);

        $this->assertSame($localPath, $resource->getLocalPath());
    }

    public function testSerializeKeepsLocalPath()
    {
        $localPath = $this->getValidLocalPath();
        $resource = $this->createLocalResource($localPath);

        $deserialized = unserialize(serialize($resource));

        $this->assertSame($localPath, $deserialized->getLocalPath());
    }
}
