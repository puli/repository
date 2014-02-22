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

use Symfony\Component\Filesystem\Filesystem;
use Webmozart\Puli\Locator\ResourceLocatorInterface;
use Webmozart\Puli\Pattern\GlobPattern;
use Webmozart\Puli\Repository\ResourceRepository;

/**
 * @since  1.0
 * @author Bernhard Schussek <bschussek@gmail.com>
 */
abstract class AbstractResourceLocatorTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var Filesystem
     */
    protected static $filesystem;

    /**
     * @var ResourceRepository
     */
    protected $repo;

    /**
     * @var ResourceLocatorInterface
     */
    protected $locator;

    /**
     * @var string
     */
    protected $fixturesDir;

    public static function setUpBeforeClass()
    {
        parent::setUpBeforeClass();

        self::$filesystem = new Filesystem();
    }

    abstract protected function dumpLocator();

    protected function setUp()
    {
        parent::setUp();

        $this->repo = new ResourceRepository();
        $this->locator = null;
        $this->fixturesDir = realpath(__DIR__.'/../Fixtures');
    }

    public function testOverrideFile()
    {
        $this->repo->add('/webmozart/puli/file1', $this->fixturesDir.'/dir1/file1');

        $this->dumpLocator();

        $file = $this->locator->get('/webmozart/puli/file1');

        $this->assertInstanceOf('Webmozart\\Puli\\Resource\\ResourceInterface', $file);
        $this->assertEquals('/webmozart/puli/file1', $file->getPath());
        $this->assertEquals($this->fixturesDir.'/dir1/file1', $file->getRealPath());
        $this->assertEquals(array($this->fixturesDir.'/dir1/file1'), $file->getAlternativePaths());

        $this->repo->add('/webmozart/puli/file1', $this->fixturesDir.'/dir1/file2');

        $this->dumpLocator();

        $file = $this->locator->get('/webmozart/puli/file1');

        $this->assertInstanceOf('Webmozart\\Puli\\Resource\\ResourceInterface', $file);
        $this->assertEquals('/webmozart/puli/file1', $file->getPath());
        $this->assertEquals($this->fixturesDir.'/dir1/file2', $file->getRealPath());
        $this->assertEquals(array($this->fixturesDir.'/dir1/file1', $this->fixturesDir.'/dir1/file2'), $file->getAlternativePaths());
    }

    public function testOverrideDirectory()
    {
        $this->repo->add('/webmozart/puli', $this->fixturesDir.'/dir1');
        $this->repo->add('/webmozart/puli', $this->fixturesDir.'/dir2');

        $this->dumpLocator();

        $dir = $this->locator->get('/webmozart/puli');

        $this->assertEquals('/webmozart/puli', $dir->getPath());
        $this->assertEquals($this->fixturesDir.'/dir2', $dir->getRealPath());
        $this->assertEquals(array($this->fixturesDir.'/dir1', $this->fixturesDir.'/dir2'), $dir->getAlternativePaths());

        $file1 = $this->locator->get('/webmozart/puli/file1');

        $this->assertInstanceOf('Webmozart\\Puli\\Resource\\ResourceInterface', $file1);
        $this->assertEquals('/webmozart/puli/file1', $file1->getPath());
        $this->assertEquals($this->fixturesDir.'/dir2/file1', $file1->getRealPath());
        $this->assertEquals(array($this->fixturesDir.'/dir1/file1', $this->fixturesDir.'/dir2/file1'), $file1->getAlternativePaths());

        $file2 = $this->locator->get('/webmozart/puli/file2');

        $this->assertInstanceOf('Webmozart\\Puli\\Resource\\ResourceInterface', $file2);
        $this->assertEquals('/webmozart/puli/file2', $file2->getPath());
        $this->assertEquals($this->fixturesDir.'/dir1/file2', $file2->getRealPath());
        $this->assertEquals(array($this->fixturesDir.'/dir1/file2'), $file2->getAlternativePaths());
    }

    public function testContainsPath()
    {
        $this->dumpLocator();

        $this->assertTrue($this->locator->contains('/'));
        $this->assertTrue($this->locator->contains('/.'));
        $this->assertTrue($this->locator->contains('/..'));
        $this->assertFalse($this->locator->contains('/webmozart'));
        $this->assertFalse($this->locator->contains('/webmozart/.'));
        $this->assertTrue($this->locator->contains('/webmozart/..'));
        $this->assertFalse($this->locator->contains('/./webmozart'));
        $this->assertFalse($this->locator->contains('/../webmozart'));
        $this->assertFalse($this->locator->contains('/webmozart/../webmozart'));
        $this->assertFalse($this->locator->contains('/webmozart/puli'));
        $this->assertFalse($this->locator->contains('/webmozart/puli/.'));
        $this->assertFalse($this->locator->contains('/webmozart/puli/..'));
        $this->assertFalse($this->locator->contains('/webmozart/./puli'));
        $this->assertFalse($this->locator->contains('/webmozart/././puli'));
        $this->assertFalse($this->locator->contains('/webmozart/../webmozart/puli'));
        $this->assertFalse($this->locator->contains('/webmozart/../../webmozart/puli'));
        $this->assertFalse($this->locator->contains('/webmozart/../puli'));
        $this->assertFalse($this->locator->contains('/webmozart/./../puli'));
        $this->assertFalse($this->locator->contains('/webmozart/.././puli'));
        $this->assertFalse($this->locator->contains('/webmozart/puli/file1'));
        $this->assertFalse($this->locator->contains('/webmozart/puli/file1/.'));
        $this->assertFalse($this->locator->contains('/webmozart/puli/file1/..'));
        $this->assertFalse($this->locator->contains('/webmozart/puli/file2'));
        $this->assertFalse($this->locator->contains('/webmozart/puli/file2/.'));
        $this->assertFalse($this->locator->contains('/webmozart/puli/file2/..'));

        $this->repo->add('/webmozart/puli', $this->fixturesDir.'/dir1');

        $this->dumpLocator();

        $this->assertTrue($this->locator->contains('/'));
        $this->assertTrue($this->locator->contains('/.'));
        $this->assertTrue($this->locator->contains('/..'));
        $this->assertTrue($this->locator->contains('/webmozart'));
        $this->assertTrue($this->locator->contains('/webmozart/.'));
        $this->assertTrue($this->locator->contains('/webmozart/..'));
        $this->assertTrue($this->locator->contains('/./webmozart'));
        $this->assertTrue($this->locator->contains('/../webmozart'));
        $this->assertTrue($this->locator->contains('/webmozart/puli'));
        $this->assertTrue($this->locator->contains('/webmozart/puli/.'));
        $this->assertTrue($this->locator->contains('/webmozart/puli/..'));
        $this->assertTrue($this->locator->contains('/webmozart/./puli'));
        $this->assertTrue($this->locator->contains('/webmozart/././puli'));
        $this->assertTrue($this->locator->contains('/webmozart/../webmozart/puli'));
        $this->assertTrue($this->locator->contains('/webmozart/../../webmozart/puli'));
        $this->assertFalse($this->locator->contains('/webmozart/../puli'));
        $this->assertFalse($this->locator->contains('/webmozart/./../puli'));
        $this->assertFalse($this->locator->contains('/webmozart/.././puli'));
        $this->assertTrue($this->locator->contains('/webmozart/puli/file1'));
        $this->assertTrue($this->locator->contains('/webmozart/puli/file1/.'));
        $this->assertTrue($this->locator->contains('/webmozart/puli/file1/..'));
        $this->assertTrue($this->locator->contains('/webmozart/puli/file2'));
        $this->assertTrue($this->locator->contains('/webmozart/puli/file2/.'));
        $this->assertTrue($this->locator->contains('/webmozart/puli/file2/..'));
    }

    public function testContainsArray()
    {
        $this->dumpLocator();

        $this->assertFalse($this->locator->contains(array(
            '/webmozart/puli/file1',
            '/webmozart/puli/file2',
        )));
        $this->assertFalse($this->locator->contains(array(
            '/webmozart/puli/file1',
            '/webmozart/puli/file2',
            '/webmozart/puli/file3',
        )));

        $this->repo->add('/webmozart/puli', $this->fixturesDir.'/dir1/*');

        $this->dumpLocator();

        $this->assertTrue($this->locator->contains(array(
            '/webmozart/puli/file1',
            '/webmozart/puli/file2',
        )));
        $this->assertFalse($this->locator->contains(array(
            '/webmozart/puli/file1',
            '/webmozart/puli/file2',
            '/webmozart/puli/file3',
        )));
    }

    public function testContainsPattern()
    {
        $this->dumpLocator();

        $this->assertFalse($this->locator->contains('/webmozart/*'));
        $this->assertFalse($this->locator->contains('/webmozart/file*'));
        $this->assertFalse($this->locator->contains('/webmozart/puli/file*'));
        $this->assertFalse($this->locator->contains('/webmozart/*/file*'));

        $this->repo->add('/webmozart/puli', $this->fixturesDir.'/dir1');

        $this->dumpLocator();

        $this->assertTrue($this->locator->contains('/webmozart/*'));
        $this->assertFalse($this->locator->contains('/webmozart/file*'));
        $this->assertTrue($this->locator->contains('/webmozart/puli/file*'));
        $this->assertTrue($this->locator->contains('/webmozart/*/file*'));
    }

    public function testContainsPatternInstance()
    {
        $this->dumpLocator();

        $this->assertFalse($this->locator->contains(new GlobPattern('/webmozart/*')));
        $this->assertFalse($this->locator->contains(new GlobPattern('/webmozart/file*')));
        $this->assertFalse($this->locator->contains(new GlobPattern('/webmozart/puli/file*')));
        $this->assertFalse($this->locator->contains(new GlobPattern('/webmozart/*/file*')));

        $this->repo->add('/webmozart/puli', $this->fixturesDir.'/dir1');

        $this->dumpLocator();

        $this->assertTrue($this->locator->contains(new GlobPattern('/webmozart/*')));
        $this->assertFalse($this->locator->contains(new GlobPattern('/webmozart/file*')));
        $this->assertTrue($this->locator->contains(new GlobPattern('/webmozart/puli/file*')));
        $this->assertTrue($this->locator->contains(new GlobPattern('/webmozart/*/file*')));
    }

    public function testContainsArrayPattern()
    {
        $this->dumpLocator();

        $this->assertFalse($this->locator->contains(array(
            '/webmozart/puli/file1',
            '/webmozart/puli/*2',
        )));

        $this->repo->add('/webmozart/puli', $this->fixturesDir.'/dir1');

        $this->dumpLocator();

        $this->assertTrue($this->locator->contains(array(
            '/webmozart/puli/file1',
            '/webmozart/puli/*2',
        )));
    }

    public function testContainsDiscardsTrailingSlash()
    {
        $this->repo->add('/webmozart/puli', $this->fixturesDir.'/dir1');

        $this->dumpLocator();

        $this->assertTrue($this->locator->contains('/webmozart/puli/'));
    }

    public function testContainsInterpretsConsecutiveSlashesAsRoot()
    {
        $this->dumpLocator();

        $this->assertTrue($this->locator->contains('///'));
    }

    /**
     * This test case actually tests the implementation of the used
     * DirectoryResourceInterface instance. It is contained in this test
     * because the all resource locators should behave identically when dealing
     * with their resources.
     */
    public function testDirectoryContains()
    {
        $this->dumpLocator();

        $this->assertFalse($this->locator->get('/')->contains('webmozart'));

        $this->repo->add('/webmozart/puli', $this->fixturesDir.'/dir1');

        $this->dumpLocator();

        $this->assertTrue($this->locator->get('/')->contains('webmozart'));
        $this->assertTrue($this->locator->get('/webmozart')->contains('puli'));
        $this->assertTrue($this->locator->get('/webmozart/puli')->contains('file1'));
        $this->assertTrue($this->locator->get('/webmozart/puli')->contains('file2'));
    }

    /**
     * This test case actually tests the implementation of the used
     * DirectoryResourceInterface instance. It is contained in this test
     * because the all resource locators should behave identically when dealing
     * with their resources.
     */
    public function testDirectoryOffsetExists()
    {
        $this->dumpLocator();

        $directory = $this->locator->get('/');

        $this->assertFalse(isset($directory['webmozart']));

        $this->repo->add('/webmozart/puli', $this->fixturesDir.'/dir1');

        $this->dumpLocator();

        $directory = $this->locator->get('/');

        $this->assertTrue(isset($directory['webmozart']));

        $directory = $this->locator->get('/webmozart/puli');

        $this->assertTrue(isset($directory['file1']));
    }

    public function testGetOne()
    {
        $this->repo->add('/webmozart/puli', $this->fixturesDir.'/dir1');

        $this->dumpLocator();

        $file = $this->locator->get('/webmozart/puli/file1');

        $this->assertInstanceOf('Webmozart\\Puli\\Resource\\ResourceInterface', $file);
        $this->assertEquals('/webmozart/puli/file1', $file->getPath());
        $this->assertEquals($this->fixturesDir.'/dir1/file1', $file->getRealPath());
    }

    public function provideManySelector()
    {
        return array(
            array('/webmozart/puli/file*'),
            array(new GlobPattern('/webmozart/puli/file*')),
            array(array(
                '/webmozart/puli/file1',
                '/webmozart/puli/file2',
            )),
            array(array(
                '/webmozart/puli/file1',
                '/webmozart/puli/*2',
            )),
            array(array(
                '/webmozart/puli/file1',
                new GlobPattern('/webmozart/puli/*2'),
            )),
        );
    }

    /**
     * @dataProvider provideManySelector
     */
    public function testGetMany($selector)
    {
        $this->repo->add('/webmozart/puli', $this->fixturesDir.'/dir1');

        $this->dumpLocator();

        $files = $this->locator->get($selector);

        $this->assertCount(2, $files);

        $this->assertInstanceOf('Webmozart\\Puli\\Resource\\ResourceInterface', $files[0]);
        $this->assertEquals('/webmozart/puli/file1', $files[0]->getPath());
        $this->assertEquals($this->fixturesDir.'/dir1/file1', $files[0]->getRealPath());

        $this->assertInstanceOf('Webmozart\\Puli\\Resource\\ResourceInterface', $files[1]);
        $this->assertEquals('/webmozart/puli/file2', $files[1]->getPath());
        $this->assertEquals($this->fixturesDir.'/dir1/file2', $files[1]->getRealPath());
    }

    public function testGetDiscardsTrailingSlash()
    {
        $this->repo->add('/webmozart/puli', $this->fixturesDir.'/dir1');

        $this->dumpLocator();

        $dir = $this->locator->get('/webmozart/puli/');

        $this->assertInstanceOf('Webmozart\\Puli\\Resource\\DirectoryResourceInterface', $dir);
        $this->assertEquals('/webmozart/puli', $dir->getPath());
        $this->assertEquals($this->fixturesDir.'/dir1', $dir->getRealPath());
    }

    public function testGetInterpretsConsecutiveSlashesAsRoot()
    {
        $this->dumpLocator();

        $dir = $this->locator->get('///');

        $this->assertInstanceOf('Webmozart\\Puli\\Resource\\DirectoryResourceInterface', $dir);
        $this->assertEquals('/', $dir->getPath());
        $this->assertNull($dir->getRealPath());
    }

    public function testGetEmptyPattern()
    {
        $this->dumpLocator();

        $this->assertEquals(array(), $this->locator->get('/foo/*'));
    }

    /**
     * @expectedException \Webmozart\Puli\Locator\ResourceNotFoundException
     */
    public function testGetExpectsValidResource()
    {
        $this->dumpLocator();

        $this->locator->get('/foo/bar');
    }

    /**
     * @expectedException \Webmozart\Puli\Locator\ResourceNotFoundException
     */
    public function testGetExpectsValidResourceArray()
    {
        $this->repo->add('/webmozart/puli', $this->fixturesDir.'/dir1');

        $this->dumpLocator();

        $this->locator->get(array(
            '/webmozart/puli/file1',
            '/foo/bar',
        ));
    }

    public function testGetDotInDirectory()
    {
        $this->repo->add('/webmozart/puli', $this->fixturesDir.'/dir1');

        $this->dumpLocator();

        $file = $this->locator->get('/webmozart/puli/.');

        $this->assertInstanceOf('Webmozart\\Puli\\Resource\\DirectoryResourceInterface', $file);
        $this->assertEquals('/webmozart/puli', $file->getPath());
        $this->assertEquals($this->fixturesDir.'/dir1', $file->getRealPath());
    }

    public function testGetDotInFile()
    {
        $this->repo->add('/webmozart/puli', $this->fixturesDir.'/dir1');

        $this->dumpLocator();

        // We support this case even though it leads to an error if done
        // on a regular file system, because recognizing files would be too
        // big a performance impact
        $file = $this->locator->get('/webmozart/puli/file1/.');

        $this->assertInstanceOf('Webmozart\\Puli\\Resource\\ResourceInterface', $file);
        $this->assertEquals('/webmozart/puli/file1', $file->getPath());
        $this->assertEquals($this->fixturesDir.'/dir1/file1', $file->getRealPath());
    }

    public function testGetDotInRoot()
    {
        $this->repo->add('/webmozart/puli', $this->fixturesDir.'/dir1');

        $this->dumpLocator();

        $file = $this->locator->get('/.');

        $this->assertInstanceOf('Webmozart\\Puli\\Resource\\DirectoryResourceInterface', $file);
        $this->assertEquals('/', $file->getPath());
        $this->assertNull($file->getRealPath());
    }

    public function testGetDotDotInDirectory()
    {
        $this->repo->add('/webmozart/puli', $this->fixturesDir.'/dir1');

        $this->dumpLocator();

        $file = $this->locator->get('/webmozart/puli/..');

        $this->assertInstanceOf('Webmozart\\Puli\\Resource\\DirectoryResourceInterface', $file);
        $this->assertEquals('/webmozart', $file->getPath());
        $this->assertNull($file->getRealPath());
    }

    public function testGetDotDotInFile()
    {
        $this->repo->add('/webmozart/puli', $this->fixturesDir.'/dir1');

        $this->dumpLocator();

        // We support this case even though it leads to an error if done
        // on a regular file system, because recognizing files would be too
        // big a performance impact
        $file = $this->locator->get('/webmozart/puli/file1/..');

        $this->assertInstanceOf('Webmozart\\Puli\\Resource\\DirectoryResourceInterface', $file);
        $this->assertEquals('/webmozart/puli', $file->getPath());
        $this->assertEquals($this->fixturesDir.'/dir1', $file->getRealPath());
    }

    public function testGetDotDotInRoot()
    {
        $this->repo->add('/webmozart/puli', $this->fixturesDir.'/dir1');

        $this->dumpLocator();

        $file = $this->locator->get('/..');

        $this->assertInstanceOf('Webmozart\\Puli\\Resource\\DirectoryResourceInterface', $file);
        $this->assertEquals('/', $file->getPath());
        $this->assertNull($file->getRealPath());
    }

    /**
     * This test case actually tests the implementation of the used
     * DirectoryResourceInterface instance. It is contained in this test
     * because the all resource locators should behave identically when dealing
     * with their resources.
     */
    public function testGetInDirectoryInstance()
    {
        $this->repo->add('/webmozart/puli', $this->fixturesDir.'/dir1');

        $this->dumpLocator();

        $file = $this->locator->get('/webmozart/puli')->get('file1');

        $this->assertInstanceOf('Webmozart\\Puli\\Resource\\ResourceInterface', $file);
        $this->assertEquals('/webmozart/puli/file1', $file->getPath());
        $this->assertEquals($this->fixturesDir.'/dir1/file1', $file->getRealPath());
    }

    /**
     * This test case actually tests the implementation of the used
     * DirectoryResourceInterface instance. It is contained in this test
     * because the all resource locators should behave identically when dealing
     * with their resources.
     *
     * @expectedException \Webmozart\Puli\Locator\ResourceNotFoundException
     */
    public function testGetDotInDirectoryInstance()
    {
        $this->repo->add('/webmozart/puli', $this->fixturesDir.'/dir1');

        $this->dumpLocator();

        $this->locator->get('/webmozart/puli')->get('.');
    }

    /**
     * This test case actually tests the implementation of the used
     * DirectoryResourceInterface instance. It is contained in this test
     * because the all resource locators should behave identically when dealing
     * with their resources.
     *
     * @expectedException \Webmozart\Puli\Locator\ResourceNotFoundException
     */
    public function testGetDotDotInDirectoryInstance()
    {
        $this->repo->add('/webmozart/puli', $this->fixturesDir.'/dir1');

        $this->dumpLocator();

        $this->locator->get('/webmozart/puli')->get('..');
    }

    /**
     * This test case actually tests the implementation of the used
     * DirectoryResourceInterface instance. It is contained in this test
     * because the all resource locators should behave identically when dealing
     * with their resources.
     */
    public function testOffsetGetInDirectoryInstance()
    {
        $this->repo->add('/webmozart/puli', $this->fixturesDir.'/dir1');

        $this->dumpLocator();

        $directory = $this->locator->get('/webmozart/puli');
        $file = $directory['file1'];

        $this->assertInstanceOf('Webmozart\\Puli\\Resource\\ResourceInterface', $file);
        $this->assertEquals('/webmozart/puli/file1', $file->getPath());
        $this->assertEquals($this->fixturesDir.'/dir1/file1', $file->getRealPath());
    }

    /**
     * This test case actually tests the implementation of the used
     * DirectoryResourceInterface instance. It is contained in this test
     * because the all resource locators should behave identically when dealing
     * with their resources.
     *
     * @expectedException \Webmozart\Puli\Locator\ResourceNotFoundException
     */
    public function testGetInDirectoryInstanceExpectsExistingFile()
    {
        $this->repo->add('/webmozart/puli', $this->fixturesDir.'/dir1');

        $this->dumpLocator();

        $this->locator->get('/webmozart/puli')->get('foo');
    }

    public function testListDirectory()
    {
        $this->dumpLocator();

        $level0 = $this->locator->listDirectory('/');

        $this->assertCount(0, $level0);
        $this->assertEquals($level0, $this->locator->listDirectory('/.'));
        $this->assertEquals($level0, $this->locator->listDirectory('/..'));

        $this->repo->add('/webmozart/puli', $this->fixturesDir.'/dir1');
        $this->repo->add('/foo/bar', $this->fixturesDir.'/dir2');

        $this->dumpLocator();

        $level0 = $this->locator->listDirectory('/');

        $this->assertCount(2, $level0);

        $this->assertInstanceOf('Webmozart\\Puli\\Resource\\DirectoryResourceInterface', $level0[0]);
        $this->assertEquals('/foo', $level0[0]->getPath());
        $this->assertNull($level0[0]->getRealPath());

        $this->assertInstanceOf('Webmozart\\Puli\\Resource\\DirectoryResourceInterface', $level0[1]);
        $this->assertEquals('/webmozart', $level0[1]->getPath());
        $this->assertNull($level0[1]->getRealPath());

        $this->assertEquals($level0, $this->locator->listDirectory('/.'));
        $this->assertEquals($level0, $this->locator->listDirectory('/..'));

        $level1 = $this->locator->listDirectory('/webmozart');

        $this->assertCount(1, $level1);

        $this->assertInstanceOf('Webmozart\\Puli\\Resource\\DirectoryResourceInterface', $level1[0]);
        $this->assertEquals('/webmozart/puli', $level1[0]->getPath());
        $this->assertEquals($this->fixturesDir.'/dir1', $level1[0]->getRealPath());

        $this->assertEquals($level1, $this->locator->listDirectory('/webmozart/.'));
        $this->assertEquals($level0, $this->locator->listDirectory('/webmozart/..'));

        $level2 = $this->locator->listDirectory('/webmozart/puli');

        $this->assertCount(2, $level2);

        $this->assertInstanceOf('Webmozart\\Puli\\Resource\\ResourceInterface', $level2[0]);
        $this->assertEquals('/webmozart/puli/file1', $level2[0]->getPath());
        $this->assertEquals($this->fixturesDir.'/dir1/file1', $level2[0]->getRealPath());

        $this->assertInstanceOf('Webmozart\\Puli\\Resource\\ResourceInterface', $level2[1]);
        $this->assertEquals('/webmozart/puli/file2', $level2[1]->getPath());
        $this->assertEquals($this->fixturesDir.'/dir1/file2', $level2[1]->getRealPath());

        $this->assertEquals($level2, $this->locator->listDirectory('/webmozart/puli/.'));
        $this->assertEquals($level1, $this->locator->listDirectory('/webmozart/puli/..'));
    }

    public function testListDirectoryDiscardsTrailingSlash()
    {
        $this->repo->add('/webmozart/puli', $this->fixturesDir.'/dir1');

        $this->dumpLocator();

        $resources = $this->locator->listDirectory('/webmozart/puli/');

        $this->assertCount(2, $resources);

        $this->assertInstanceOf('Webmozart\\Puli\\Resource\\ResourceInterface', $resources[0]);
        $this->assertEquals('/webmozart/puli/file1', $resources[0]->getPath());
        $this->assertEquals($this->fixturesDir.'/dir1/file1', $resources[0]->getRealPath());

        $this->assertInstanceOf('Webmozart\\Puli\\Resource\\ResourceInterface', $resources[1]);
        $this->assertEquals('/webmozart/puli/file2', $resources[1]->getPath());
        $this->assertEquals($this->fixturesDir.'/dir1/file2', $resources[1]->getRealPath());
    }

    /**
     * @expectedException \Webmozart\Puli\Locator\ResourceNotFoundException
     */
    public function testListDirectoryExpectsValidPath()
    {
        $this->dumpLocator();

        $this->locator->listDirectory('/foo/bar');
    }

    /**
     * @expectedException \Webmozart\Puli\Repository\NoDirectoryException
     */
    public function testListDirectoryExpectsDirectory()
    {
        $this->repo->add('/webmozart/puli', $this->fixturesDir.'/dir1');

        $this->dumpLocator();

        $this->locator->listDirectory('/webmozart/puli/file1');
    }

    /**
     * @expectedException \Webmozart\Puli\Repository\NoDirectoryException
     */
    public function testListDotDirectoryExpectsDirectory()
    {
        $this->repo->add('/webmozart/puli', $this->fixturesDir.'/dir1');

        $this->dumpLocator();

        $this->locator->listDirectory('/webmozart/puli/file1/.');
    }

    public function testListDotDotDirectoryInFile()
    {
        $this->repo->add('/webmozart/puli', $this->fixturesDir.'/dir1');

        $this->dumpLocator();

        // We support this case even though it leads to an error if done
        // on a regular file system, because recognizing files would be too
        // big a performance impact
        $resources = $this->locator->listDirectory('/webmozart/puli/file1/..');

        $this->assertCount(2, $resources);

        $this->assertInstanceOf('Webmozart\\Puli\\Resource\\ResourceInterface', $resources[0]);
        $this->assertEquals('/webmozart/puli/file1', $resources[0]->getPath());
        $this->assertEquals($this->fixturesDir.'/dir1/file1', $resources[0]->getRealPath());

        $this->assertInstanceOf('Webmozart\\Puli\\Resource\\ResourceInterface', $resources[1]);
        $this->assertEquals('/webmozart/puli/file2', $resources[1]->getPath());
        $this->assertEquals($this->fixturesDir.'/dir1/file2', $resources[1]->getRealPath());
    }

    public function testListDirectoryDoesNotShowRemovedFiles()
    {
        $this->repo->add('/webmozart/puli', $this->fixturesDir.'/dir1');

        $this->repo->remove('/webmozart/puli/file1');

        $this->dumpLocator();

        $resources = $this->locator->listDirectory('/webmozart/puli/');

        $this->assertCount(1, $resources);

        $this->assertInstanceOf('Webmozart\\Puli\\Resource\\ResourceInterface', $resources[0]);
        $this->assertEquals('/webmozart/puli/file2', $resources[0]->getPath());
        $this->assertEquals($this->fixturesDir.'/dir1/file2', $resources[0]->getRealPath());
    }

    /**
     * This test case actually tests the implementation of the used
     * DirectoryResourceInterface instance. It is contained in this test
     * because the all resource locators should behave identically when dealing
     * with their resources.
     */
    public function testListDirectoryInstance()
    {
        $this->dumpLocator();

        $level0 = $this->locator->get('/')->all();

        $this->assertCount(0, $level0);
        $this->assertEquals($level0, $this->locator->get('/.')->all());
        $this->assertEquals($level0, $this->locator->get('/..')->all());

        $this->repo->add('/webmozart/puli', $this->fixturesDir.'/dir1');
        $this->repo->add('/foo/bar', $this->fixturesDir.'/dir2');

        $this->dumpLocator();

        $level0 = $this->locator->get('/')->all();

        $this->assertCount(2, $level0);

        $this->assertInstanceOf('Webmozart\\Puli\\Resource\\DirectoryResourceInterface', $level0[0]);
        $this->assertEquals('/foo', $level0[0]->getPath());
        $this->assertNull($level0[0]->getRealPath());

        $this->assertInstanceOf('Webmozart\\Puli\\Resource\\DirectoryResourceInterface', $level0[1]);
        $this->assertEquals('/webmozart', $level0[1]->getPath());
        $this->assertNull($level0[1]->getRealPath());

        $this->assertEquals($level0, $this->locator->get('/.')->all());
        $this->assertEquals($level0, $this->locator->get('/..')->all());

        $level1 = $this->locator->get('/webmozart')->all();

        $this->assertCount(1, $level1);

        $this->assertInstanceOf('Webmozart\\Puli\\Resource\\DirectoryResourceInterface', $level1[0]);
        $this->assertEquals('/webmozart/puli', $level1[0]->getPath());
        $this->assertEquals($this->fixturesDir.'/dir1', $level1[0]->getRealPath());

        $this->assertEquals($level1, $this->locator->get('/webmozart/.')->all());
        $this->assertEquals($level0, $this->locator->get('/webmozart/..')->all());

        $level2 = $this->locator->get('/webmozart/puli')->all();

        $this->assertCount(2, $level2);

        $this->assertInstanceOf('Webmozart\\Puli\\Resource\\ResourceInterface', $level2[0]);
        $this->assertEquals('/webmozart/puli/file1', $level2[0]->getPath());
        $this->assertEquals($this->fixturesDir.'/dir1/file1', $level2[0]->getRealPath());

        $this->assertInstanceOf('Webmozart\\Puli\\Resource\\ResourceInterface', $level2[1]);
        $this->assertEquals('/webmozart/puli/file2', $level2[1]->getPath());
        $this->assertEquals($this->fixturesDir.'/dir1/file2', $level2[1]->getRealPath());

        $this->assertEquals($level2, $this->locator->get('/webmozart/puli/.')->all());
        $this->assertEquals($level1, $this->locator->get('/webmozart/puli/..')->all());
    }

    /**
     * This test case actually tests the implementation of the used
     * DirectoryResourceInterface instance. It is contained in this test
     * because the all resource locators should behave identically when dealing
     * with their resources.
     */
    public function testIterateDirectory()
    {
        $this->repo->add('/webmozart/puli', $this->fixturesDir.'/dir1');

        $this->dumpLocator();

        $resources = iterator_to_array($this->locator->get('/webmozart/puli'));

        $this->assertCount(2, $resources);

        $this->assertInstanceOf('Webmozart\\Puli\\Resource\\ResourceInterface', $resources[0]);
        $this->assertEquals('/webmozart/puli/file1', $resources[0]->getPath());
        $this->assertEquals($this->fixturesDir.'/dir1/file1', $resources[0]->getRealPath());

        $this->assertInstanceOf('Webmozart\\Puli\\Resource\\ResourceInterface', $resources[1]);
        $this->assertEquals('/webmozart/puli/file2', $resources[1]->getPath());
        $this->assertEquals($this->fixturesDir.'/dir1/file2', $resources[1]->getRealPath());
    }

    /**
     * This test case actually tests the implementation of the used
     * DirectoryResourceInterface instance. It is contained in this test
     * because the all resource locators should behave identically when dealing
     * with their resources.
     */
    public function testCountDirectory()
    {
        $this->repo->add('/webmozart/puli', $this->fixturesDir.'/dir1');

        $this->dumpLocator();

        $this->assertCount(2, $this->locator->get('/webmozart/puli'));
    }

    public function testGetByTag()
    {
        $this->repo->add('/webmozart/puli', $this->fixturesDir.'/dir1');

        $this->repo->tag('/webmozart/puli/file1', 'webmozart/tag');

        $this->dumpLocator();

        $results = array($this->locator->get('/webmozart/puli/file1'));

        $this->assertEquals($results, $this->locator->getByTag('webmozart/tag'));
    }

    public function testGetByTagIgnoresNonExistingTags()
    {
        $this->dumpLocator();

        $this->assertEquals(array(), $this->locator->getByTag('foo/bar'));
    }

    public function testGetTags()
    {
        $this->repo->add('/webmozart/puli', $this->fixturesDir.'/dir1');

        $this->repo->tag('/webmozart/puli/file1', 'webmozart/tag1');

        $this->dumpLocator();

        $tags = $this->locator->getTags();

        $this->assertCount(1, $tags);
        $this->assertEquals('webmozart/tag1', $tags[0]);

        $this->repo->tag('/webmozart/puli/file2', 'webmozart/tag2');

        $this->dumpLocator();

        $tags = $this->locator->getTags();

        $this->assertCount(2, $tags);
        $this->assertEquals('webmozart/tag1', $tags[0]);
        $this->assertEquals('webmozart/tag2', $tags[1]);

        $this->repo->untag('/webmozart/puli/file1', 'webmozart/tag1');

        $this->dumpLocator();

        $tags = $this->locator->getTags();

        $this->assertCount(1, $tags);
        $this->assertEquals('webmozart/tag2', $tags[0]);
    }

    public function testGetTagsReturnsSortedResult()
    {
        $this->repo->add('/webmozart/puli', $this->fixturesDir.'/dir1');

        $this->repo->tag('/webmozart/puli/file1', 'webmozart/foo');
        $this->repo->tag('/webmozart/puli/file1', 'webmozart/bar');

        $this->dumpLocator();

        $tags = $this->locator->getTags();

        $this->assertCount(2, $tags);
        $this->assertEquals('webmozart/bar', $tags[0]);
        $this->assertEquals('webmozart/foo', $tags[1]);
    }
}
