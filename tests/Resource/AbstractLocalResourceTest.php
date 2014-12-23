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
     *
     * @return Resource
     */
    protected function createResource($path = null)
    {
        return $this->createLocalResource($this->getValidLocalPath(), $path);
    }

    /**
     * @param string               $localPath
     * @param string|null          $path
     * @param OverriddenPathLoader $pathLoader
     *
     * @return LocalResource
     */
    abstract protected function createLocalResource($localPath, $path = null, OverriddenPathLoader $pathLoader = null);

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

    public function testGetAllLocalPaths()
    {
        $localPath = $this->getValidLocalPath();
        $resource = $this->createLocalResource($localPath);

        $this->assertSame(array($localPath), $resource->getAllLocalPaths());
    }

    public function testAttachDoesNotChangeAllLocalPaths()
    {
        $localPath = $this->getValidLocalPath();
        $resource = $this->createLocalResource($localPath);
        $resource->attachTo($this->repo);

        $this->assertSame(array($localPath), $resource->getAllLocalPaths());
    }

    public function testDetachDoesNotChangeAllLocalPaths()
    {
        $localPath = $this->getValidLocalPath();
        $resource = $this->createLocalResource($localPath);
        $resource->attachTo($this->repo);
        $resource->detach();

        $this->assertSame(array($localPath), $resource->getAllLocalPaths());
    }

    public function testLoadOverriddenPathsFromPathLoader()
    {
        $loader = $this->getMock('Puli\Repository\Resource\OverriddenPathLoader');

        $localPath = $this->getValidLocalPath();
        $resource = $this->createLocalResource($localPath, null, $loader);

        $loader->expects($this->once())
            ->method('loadOverriddenPaths')
            ->with($resource)
            ->will($this->returnValue(array('/loaded/path')));

        // The actual local path always comes last
        $this->assertSame(array('/loaded/path', $localPath), $resource->getAllLocalPaths());

        // Loader is called only once even for multiple calls
        $this->assertSame(array('/loaded/path', $localPath), $resource->getAllLocalPaths());
    }

    public function testAttachDoesNotChangeLoadedOverriddenPaths()
    {
        $loader = $this->getMock('Puli\Repository\Resource\OverriddenPathLoader');

        $localPath = $this->getValidLocalPath();
        $resource = $this->createLocalResource($localPath, null, $loader);

        $loader->expects($this->once())
            ->method('loadOverriddenPaths')
            ->with($resource)
            ->will($this->returnValue(array('/loaded/path')));

        $resource->attachTo($this->repo);

        $this->assertSame(array('/loaded/path', $localPath), $resource->getAllLocalPaths());
    }

    public function testDetachDoesNotChangeLoadedOverriddenPaths()
    {
        $loader = $this->getMock('Puli\Repository\Resource\OverriddenPathLoader');

        $localPath = $this->getValidLocalPath();
        $resource = $this->createLocalResource($localPath, null, $loader);

        $loader->expects($this->once())
            ->method('loadOverriddenPaths')
            ->with($resource)
            ->will($this->returnValue(array('/loaded/path')));

        $resource->detach();

        $this->assertSame(array('/loaded/path', $localPath), $resource->getAllLocalPaths());
    }

    /**
     * @expectedException \Puli\Repository\UnsupportedResourceException
     */
    public function testOverrideFailsIfNotLocalResource()
    {
        $resource = $this->createResource();

        $resource->override($this->getMock('Puli\Repository\Resource\Resource'));
    }

    public function testOverride()
    {
        $localPath = $this->getValidLocalPath();
        $overriddenLocalPath = $this->getValidLocalPath2();

        $resource = $this->createLocalResource($localPath);
        $resource->override($this->createLocalResource($overriddenLocalPath));

        $this->assertSame($localPath, $resource->getLocalPath());
        $this->assertSame(array(
            $overriddenLocalPath,
            $localPath,
        ), $resource->getAllLocalPaths());
    }

    public function testOverrideOverridingResource()
    {
        $localPath = $this->getValidLocalPath();
        $firstOverriddenPath = $this->getValidLocalPath2();
        $secondOverriddenPath = $this->getValidLocalPath2();

        $overriding = $this->createLocalResource($secondOverriddenPath);
        $overriding->override($this->createLocalResource($firstOverriddenPath));

        $resource = $this->createLocalResource($localPath);
        $resource->override($overriding);

        $this->assertSame($localPath, $resource->getLocalPath());
        $this->assertSame(array(
            $firstOverriddenPath,
            $secondOverriddenPath,
            $localPath,
        ), $resource->getAllLocalPaths());
    }

    public function testOverrideTwoResources()
    {
        $localPath = $this->getValidLocalPath();
        $firstOverriddenPath = $this->getValidLocalPath2();
        $secondOverriddenPath = $this->getValidLocalPath2();

        $resource = $this->createLocalResource($localPath);
        $resource->override($this->createLocalResource($secondOverriddenPath));
        $resource->override($this->createLocalResource($firstOverriddenPath));

        $this->assertSame($localPath, $resource->getLocalPath());
        $this->assertSame(array(
            $firstOverriddenPath,
            $secondOverriddenPath,
            $localPath,
        ), $resource->getAllLocalPaths());
    }

    public function testOverrideLoadsPathsForOverridingResource()
    {
        $loader = $this->getMock('Puli\Repository\Resource\OverriddenPathLoader');

        $localPath = $this->getValidLocalPath();
        $overriddenLocalPath = $this->getValidLocalPath2();

        $resource = $this->createLocalResource($localPath, null, $loader);

        $loader->expects($this->once())
            ->method('loadOverriddenPaths')
            ->with($resource)
            ->will($this->returnValue(array('/loaded/path')));

        $resource->override($this->createLocalResource($overriddenLocalPath));

        $this->assertSame($localPath, $resource->getLocalPath());
        $this->assertSame(array(
            $overriddenLocalPath,
            '/loaded/path',
            $localPath,
        ), $resource->getAllLocalPaths());
    }

    public function testOverrideLoadsPathsForOverriddenResource()
    {
        $loader = $this->getMock('Puli\Repository\Resource\OverriddenPathLoader');

        $localPath = $this->getValidLocalPath();
        $overriddenLocalPath = $this->getValidLocalPath2();

        $resource = $this->createLocalResource($localPath);
        $overridden = $this->createLocalResource($overriddenLocalPath, null, $loader);

        $loader->expects($this->once())
            ->method('loadOverriddenPaths')
            ->with($overridden)
            ->will($this->returnValue(array('/loaded/path')));

        $resource->override($overridden);

        $this->assertSame($localPath, $resource->getLocalPath());
        $this->assertSame(array(
            '/loaded/path',
            $overriddenLocalPath,
            $localPath,
        ), $resource->getAllLocalPaths());
    }

    public function testSerializeKeepsLocalPath()
    {
        $localPath = $this->getValidLocalPath();
        $resource = $this->createLocalResource($localPath);

        $deserialized = unserialize(serialize($resource));

        $this->assertSame($localPath, $deserialized->getLocalPath());
        $this->assertSame(array($localPath), $deserialized->getAllLocalPaths());
    }

    public function testSerializeKeepsOverriddenPaths()
    {
        $localPath = $this->getValidLocalPath();
        $overriddenLocalPath = $this->getValidLocalPath2();

        $resource = $this->createLocalResource($localPath);
        $resource->override($this->createLocalResource($overriddenLocalPath));

        $deserialized = unserialize(serialize($resource));

        $this->assertSame($localPath, $deserialized->getLocalPath());
        $this->assertSame(array(
            $overriddenLocalPath,
            $localPath,
        ), $deserialized->getAllLocalPaths());
    }

    public function testSerializeKeepsLoadedPaths()
    {
        $loader = $this->getMock('Puli\Repository\Resource\OverriddenPathLoader');

        $localPath = $this->getValidLocalPath();
        $resource = $this->createLocalResource($localPath, null, $loader);

        $loader->expects($this->once())
            ->method('loadOverriddenPaths')
            ->with($resource)
            ->will($this->returnValue(array('/loaded/path')));

        $resource->getAllLocalPaths();

        $deserialized = unserialize(serialize($resource));

        $this->assertSame($localPath, $deserialized->getLocalPath());
        $this->assertSame(array(
            '/loaded/path',
            $localPath,
        ), $deserialized->getAllLocalPaths());
    }

    public function testSerializeLoadsPathsIfNotYetLoaded()
    {
        $loader = $this->getMock('Puli\Repository\Resource\OverriddenPathLoader');

        $localPath = $this->getValidLocalPath();
        $resource = $this->createLocalResource($localPath, null, $loader);

        $loader->expects($this->once())
            ->method('loadOverriddenPaths')
            ->with($resource)
            ->will($this->returnValue(array('/loaded/path')));

        $deserialized = unserialize(serialize($resource));

        $this->assertSame($localPath, $deserialized->getLocalPath());
        $this->assertSame(array(
            '/loaded/path',
            $localPath,
        ), $deserialized->getAllLocalPaths());
    }
}
