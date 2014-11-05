<?php

/*
 * This file is part of the Puli package.
 *
 * (c) Bernhard Schussek <bschussek@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Webmozart\Puli\Tests\Filesystem;

use Webmozart\Puli\Filesystem\FilesystemLocator;
use Webmozart\Puli\Pattern\GlobPattern;

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

        $this->assertInstanceOf('Webmozart\\Puli\\Filesystem\\Resource\\LocalDirectoryResource', $resource);
        $this->assertSame('/dir1', $resource->getPath());
        $this->assertSame($this->fixturesDir.'/dir1', $resource->getLocalPath());
    }

    public function testGetFile()
    {
        $resource = $this->locator->get('/dir1/file1');

        $this->assertInstanceOf('Webmozart\\Puli\\Filesystem\\Resource\\LocalFileResource', $resource);
        $this->assertSame('/dir1/file1', $resource->getPath());
        $this->assertSame($this->fixturesDir.'/dir1/file1', $resource->getLocalPath());
    }

    public function testGetFileReturnsRealPath()
    {
        $resource = $this->locator->get('/dir1/../dir1/file1');

        $this->assertInstanceOf('Webmozart\\Puli\\Filesystem\\Resource\\LocalFileResource', $resource);
        $this->assertSame('/dir1/file1', $resource->getPath());
        $this->assertSame($this->fixturesDir.'/dir1/file1', $resource->getLocalPath());
    }

    public function testGetLink()
    {
        $resource = $this->locator->get('/dir2/file1-link');

        $this->assertInstanceOf('Webmozart\\Puli\\Filesystem\\Resource\\LocalFileResource', $resource);
        $this->assertSame('/dir2/file1-link', $resource->getPath());
        $this->assertSame($this->fixturesDir.'/dir2/file1-link', $resource->getLocalPath());
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

        $this->assertInstanceOf('Webmozart\\Puli\\Filesystem\\Resource\\LocalDirectoryResource', $resource);
        $this->assertSame('/dir1', $resource->getPath());
        $this->assertSame($this->fixturesDir.'/dir1', $resource->getLocalPath());
    }

    public function testGetDotDot()
    {
        $resource = $this->locator->get('/dir1/file1/..');

        $this->assertInstanceOf('Webmozart\\Puli\\Filesystem\\Resource\\LocalDirectoryResource', $resource);
        $this->assertSame('/dir1', $resource->getPath());
        $this->assertSame($this->fixturesDir.'/dir1', $resource->getLocalPath());
    }

    public function testGetFromDirectoryInstance()
    {
        $resource = $this->locator->get('/')->get('dir1');

        $this->assertInstanceOf('Webmozart\\Puli\\Filesystem\\Resource\\LocalDirectoryResource', $resource);
        $this->assertSame('/dir1', $resource->getPath());
        $this->assertSame($this->fixturesDir.'/dir1', $resource->getLocalPath());
    }

    public function testFind()
    {
        $resources = $this->locator->find('/dir1/*');

        $this->assertCount(2, $resources);
        $this->assertInstanceOf('Webmozart\\Puli\\Filesystem\\Resource\\LocalResourceCollection', $resources);

        $this->assertInstanceOf('Webmozart\\Puli\\Filesystem\\Resource\\LocalFileResource', $resources[0]);
        $this->assertSame('/dir1/file1', $resources[0]->getPath());
        $this->assertSame($this->fixturesDir.'/dir1/file1', $resources[0]->getLocalPath());

        $this->assertInstanceOf('Webmozart\\Puli\\Filesystem\\Resource\\LocalFileResource', $resources[1]);
        $this->assertSame('/dir1/file2', $resources[1]->getPath());
        $this->assertSame($this->fixturesDir.'/dir1/file2', $resources[1]->getLocalPath());
    }

    public function testFindPatternInstance()
    {
        $resources = $this->locator->find(new GlobPattern('/dir1/*'));

        $this->assertCount(2, $resources);

        $this->assertInstanceOf('Webmozart\\Puli\\Filesystem\\Resource\\LocalFileResource', $resources[0]);
        $this->assertSame('/dir1/file1', $resources[0]->getPath());
        $this->assertSame($this->fixturesDir.'/dir1/file1', $resources[0]->getLocalPath());
        $this->assertSame(array($resources[0]->getLocalPath()), $resources[0]->getAlternativePaths());

        $this->assertInstanceOf('Webmozart\\Puli\\Filesystem\\Resource\\LocalFileResource', $resources[1]);
        $this->assertSame('/dir1/file2', $resources[1]->getPath());
        $this->assertSame($this->fixturesDir.'/dir1/file2', $resources[1]->getLocalPath());
        $this->assertSame(array($resources[1]->getLocalPath()), $resources[1]->getAlternativePaths());
    }

    /**
     * @expectedException \Webmozart\Puli\Locator\ResourceNotFoundException
     */
    public function testFindNonExisting()
    {
        $this->assertCount(0, $this->locator->get('/foo'));
    }

    /**
     * @expectedException \Webmozart\Puli\Locator\ResourceNotFoundException
     */
    public function testFindNonAbsolute()
    {
        $this->assertCount(0, $this->locator->get('foo'));
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

    public function testContainsPattern()
    {
        $this->assertTrue($this->locator->contains('/*'));
        $this->assertFalse($this->locator->contains('/fo*'));
    }

    public function testListDirectory()
    {
        $resources = $this->locator->listDirectory('/dir1');

        $this->assertCount(2, $resources);
        $this->assertInstanceOf('Webmozart\\Puli\\Filesystem\\Resource\\LocalResourceCollection', $resources);

        $this->assertInstanceOf('Webmozart\\Puli\\Filesystem\\Resource\\LocalFileResource', $resources['file1']);
        $this->assertSame('/dir1/file1', $resources['file1']->getPath());
        $this->assertSame($this->fixturesDir.'/dir1/file1', $resources['file1']->getLocalPath());
        $this->assertSame(array($resources['file1']->getLocalPath()), $resources['file1']->getAlternativePaths());

        $this->assertInstanceOf('Webmozart\\Puli\\Filesystem\\Resource\\LocalFileResource', $resources['file2']);
        $this->assertSame('/dir1/file2', $resources['file2']->getPath());
        $this->assertSame($this->fixturesDir.'/dir1/file2', $resources['file2']->getLocalPath());
        $this->assertSame(array($resources['file2']->getLocalPath()), $resources['file2']->getAlternativePaths());
    }

    public function testListDirectoryWithDots()
    {
        $resources = $this->locator->listDirectory('/dir2');

        $this->assertCount(3, $resources);

        $this->assertInstanceOf('Webmozart\\Puli\\Filesystem\\Resource\\LocalFileResource', $resources['.dotfile']);
        $this->assertSame('/dir2/.dotfile', $resources['.dotfile']->getPath());
        $this->assertSame($this->fixturesDir.'/dir2/.dotfile', $resources['.dotfile']->getLocalPath());

        $this->assertInstanceOf('Webmozart\\Puli\\Filesystem\\Resource\\LocalFileResource', $resources['file1']);
        $this->assertSame('/dir2/file1', $resources['file1']->getPath());
        $this->assertSame($this->fixturesDir.'/dir2/file1', $resources['file1']->getLocalPath());

        $this->assertInstanceOf('Webmozart\\Puli\\Filesystem\\Resource\\LocalFileResource', $resources['file1-link']);
        $this->assertSame('/dir2/file1-link', $resources['file1-link']->getPath());
        $this->assertSame($this->fixturesDir.'/dir2/file1-link', $resources['file1-link']->getLocalPath());
    }

    public function testListDirectoryInstance()
    {
        $resources = $this->locator->get('/dir1')->listEntries();

        $this->assertCount(2, $resources);

        $this->assertInstanceOf('Webmozart\\Puli\\Filesystem\\Resource\\LocalFileResource', $resources['file1']);
        $this->assertSame('/dir1/file1', $resources['file1']->getPath());
        $this->assertSame($this->fixturesDir.'/dir1/file1', $resources['file1']->getLocalPath());
        $this->assertSame(array($resources['file1']->getLocalPath()), $resources['file1']->getAlternativePaths());

        $this->assertInstanceOf('Webmozart\\Puli\\Filesystem\\Resource\\LocalFileResource', $resources['file2']);
        $this->assertSame('/dir1/file2', $resources['file2']->getPath());
        $this->assertSame($this->fixturesDir.'/dir1/file2', $resources['file2']->getLocalPath());
        $this->assertSame(array($resources['file2']->getLocalPath()), $resources['file2']->getAlternativePaths());
    }
}
