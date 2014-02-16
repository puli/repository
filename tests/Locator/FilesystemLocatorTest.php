<?php

/*
 * This file is part of the Puli package.
 *
 * (c) Bernhard Schussek <bschussek@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Webmozart\Puli\Tests\Locator;

use Webmozart\Puli\Locator\FilesystemLocator;

/**
 * @since  1.0
 * @author Bernhard Schussek <bschussek@gmail.com>
 */
class FilesystemLocatorTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var FilesystemLocator
     */
    private $locator;

    private $fixturesDir;

    protected function setUp()
    {
        $this->fixturesDir = realpath(__DIR__.'/../Fixtures');
        $this->locator = new FilesystemLocator($this->fixturesDir);
    }

    /**
     * @expectedException \InvalidArgumentException
     */
    public function testPassNonExistingRootDirectory()
    {
        new FilesystemLocator($this->fixturesDir.'/foo');
    }

    /**
     * @expectedException \InvalidArgumentException
     */
    public function testPassFileAsRootDirectory()
    {
        new FilesystemLocator($this->fixturesDir.'/dir1/file1');
    }

    public function testGetDirectory()
    {
        $resource = $this->locator->get('/dir1');

        $this->assertInstanceOf('Webmozart\\Puli\\Resource\\DirectoryResourceInterface', $resource);
        $this->assertSame('/dir1', $resource->getRepositoryPath());
        $this->assertSame($this->fixturesDir.'/dir1', $resource->getPath());
        $this->assertSame(array($resource->getPath()), $resource->getAlternativePaths());
    }

    public function testGetFile()
    {
        $resource = $this->locator->get('/dir1/file1');

        $this->assertInstanceOf('Webmozart\\Puli\\Resource\\ResourceInterface', $resource);
        $this->assertSame('/dir1/file1', $resource->getRepositoryPath());
        $this->assertSame($this->fixturesDir.'/dir1/file1', $resource->getPath());
        $this->assertSame(array($resource->getPath()), $resource->getAlternativePaths());
    }

    public function testGetMany()
    {
        $resources = $this->locator->get(array('/dir1', '/dir2'));

        $this->assertCount(2, $resources);

        $this->assertInstanceOf('Webmozart\\Puli\\Resource\\DirectoryResourceInterface', $resources[0]);
        $this->assertSame('/dir1', $resources[0]->getRepositoryPath());
        $this->assertSame($this->fixturesDir.'/dir1', $resources[0]->getPath());
        $this->assertSame(array($resources[0]->getPath()), $resources[0]->getAlternativePaths());

        $this->assertInstanceOf('Webmozart\\Puli\\Resource\\DirectoryResourceInterface', $resources[1]);
        $this->assertSame('/dir2', $resources[1]->getRepositoryPath());
        $this->assertSame($this->fixturesDir.'/dir2', $resources[1]->getPath());
        $this->assertSame(array($resources[1]->getPath()), $resources[1]->getAlternativePaths());
    }

    public function testGetPattern()
    {
        $resources = $this->locator->get('/*');

        $this->assertCount(2, $resources);

        $this->assertInstanceOf('Webmozart\\Puli\\Resource\\DirectoryResourceInterface', $resources[0]);
        $this->assertSame('/dir1', $resources[0]->getRepositoryPath());
        $this->assertSame($this->fixturesDir.'/dir1', $resources[0]->getPath());
        $this->assertSame(array($resources[0]->getPath()), $resources[0]->getAlternativePaths());

        $this->assertInstanceOf('Webmozart\\Puli\\Resource\\DirectoryResourceInterface', $resources[1]);
        $this->assertSame('/dir2', $resources[1]->getRepositoryPath());
        $this->assertSame($this->fixturesDir.'/dir2', $resources[1]->getPath());
        $this->assertSame(array($resources[1]->getPath()), $resources[1]->getAlternativePaths());
    }

    /**
     * @expectedException \Webmozart\Puli\Locator\ResourceNotFoundException
     */
    public function testGetNonExisting()
    {
        $this->locator->get('/foo');
    }

    /**
     * @expectedException \Webmozart\Puli\Locator\ResourceNotFoundException
     */
    public function testGetNonAbsolute()
    {
        $this->locator->get('foo');
    }

    public function testGetDot()
    {
        $resource = $this->locator->get('/dir1/.');

        $this->assertInstanceOf('Webmozart\\Puli\\Resource\\DirectoryResourceInterface', $resource);
        $this->assertSame('/dir1', $resource->getRepositoryPath());
        $this->assertSame($this->fixturesDir.'/dir1', $resource->getPath());
        $this->assertSame(array($resource->getPath()), $resource->getAlternativePaths());
    }

    public function testGetDotDot()
    {
        $resource = $this->locator->get('/dir1/file1/..');

        $this->assertInstanceOf('Webmozart\\Puli\\Resource\\DirectoryResourceInterface', $resource);
        $this->assertSame('/dir1', $resource->getRepositoryPath());
        $this->assertSame($this->fixturesDir.'/dir1', $resource->getPath());
        $this->assertSame(array($resource->getPath()), $resource->getAlternativePaths());
    }

    public function testGetFromDirectoryInstance()
    {
        $resource = $this->locator->get('/')->get('dir1');

        $this->assertInstanceOf('Webmozart\\Puli\\Resource\\DirectoryResourceInterface', $resource);
        $this->assertSame('/dir1', $resource->getRepositoryPath());
        $this->assertSame($this->fixturesDir.'/dir1', $resource->getPath());
        $this->assertSame(array($resource->getPath()), $resource->getAlternativePaths());
    }

    public function testContainsOne()
    {
        $this->assertTrue($this->locator->contains('/'));
        $this->assertTrue($this->locator->contains('/.'));
        $this->assertTrue($this->locator->contains('/..'));
        $this->assertTrue($this->locator->contains('/./..'));
        $this->assertTrue($this->locator->contains('/../..'));
        $this->assertTrue($this->locator->contains('/dir1'));
        $this->assertTrue($this->locator->contains('/dir1/.'));
        $this->assertTrue($this->locator->contains('/dir1/..'));
        $this->assertTrue($this->locator->contains('/dir1/./..'));
        $this->assertTrue($this->locator->contains('/dir1/../..'));
        $this->assertTrue($this->locator->contains('/dir2'));
        $this->assertFalse($this->locator->contains('/foo'));
    }

    public function testContainsMany()
    {
        $this->assertTrue($this->locator->contains(array('/dir1', '/dir2')));
        $this->assertFalse($this->locator->contains(array('/dir1', '/foo')));
    }

    public function testContainsPattern()
    {
        $this->assertTrue($this->locator->contains('/*'));
        $this->assertFalse($this->locator->contains('/fo*'));
    }

    public function testListDirectory()
    {
        $resources = $this->locator->listDirectory('/');

        $this->assertCount(2, $resources);

        $this->assertInstanceOf('Webmozart\\Puli\\Resource\\DirectoryResourceInterface', $resources[0]);
        $this->assertSame('/dir1', $resources[0]->getRepositoryPath());
        $this->assertSame($this->fixturesDir.'/dir1', $resources[0]->getPath());
        $this->assertSame(array($resources[0]->getPath()), $resources[0]->getAlternativePaths());

        $this->assertInstanceOf('Webmozart\\Puli\\Resource\\DirectoryResourceInterface', $resources[1]);
        $this->assertSame('/dir2', $resources[1]->getRepositoryPath());
        $this->assertSame($this->fixturesDir.'/dir2', $resources[1]->getPath());
        $this->assertSame(array($resources[1]->getPath()), $resources[1]->getAlternativePaths());
    }

    public function testListDirectoryWithDots()
    {
        $resources = $this->locator->listDirectory('/dir2');

        $this->assertCount(3, $resources);

        $this->assertInstanceOf('Webmozart\\Puli\\Resource\\ResourceInterface', $resources[0]);
        $this->assertSame('/dir2/.dotfile', $resources[0]->getRepositoryPath());
        $this->assertSame($this->fixturesDir.'/dir2/.dotfile', $resources[0]->getPath());
        $this->assertSame(array($resources[0]->getPath()), $resources[0]->getAlternativePaths());

        $this->assertInstanceOf('Webmozart\\Puli\\Resource\\ResourceInterface', $resources[1]);
        $this->assertSame('/dir2/file1', $resources[1]->getRepositoryPath());
        $this->assertSame($this->fixturesDir.'/dir2/file1', $resources[1]->getPath());
        $this->assertSame(array($resources[1]->getPath()), $resources[1]->getAlternativePaths());

        $this->assertInstanceOf('Webmozart\\Puli\\Resource\\ResourceInterface', $resources[2]);
        $this->assertSame('/dir2/file1-link', $resources[2]->getRepositoryPath());
        $this->assertSame($this->fixturesDir.'/dir2/file1-link', $resources[2]->getPath());
        $this->assertSame(array($resources[2]->getPath()), $resources[2]->getAlternativePaths());
    }

    public function testListDirectoryInstance()
    {
        $resources = $this->locator->get('/')->all();

        $this->assertCount(2, $resources);

        $this->assertInstanceOf('Webmozart\\Puli\\Resource\\DirectoryResourceInterface', $resources[0]);
        $this->assertSame('/dir1', $resources[0]->getRepositoryPath());
        $this->assertSame($this->fixturesDir.'/dir1', $resources[0]->getPath());
        $this->assertSame(array($resources[0]->getPath()), $resources[0]->getAlternativePaths());

        $this->assertInstanceOf('Webmozart\\Puli\\Resource\\DirectoryResourceInterface', $resources[1]);
        $this->assertSame('/dir2', $resources[1]->getRepositoryPath());
        $this->assertSame($this->fixturesDir.'/dir2', $resources[1]->getPath());
        $this->assertSame(array($resources[1]->getPath()), $resources[1]->getAlternativePaths());
    }
}
