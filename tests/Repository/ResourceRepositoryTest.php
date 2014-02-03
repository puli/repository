<?php

/*
 * This file is part of the Puli package.
 *
 * (c) Bernhard Schussek <bschussek@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Webmozart\tests\Repository;

use Webmozart\Puli\Pattern\GlobPattern;
use Webmozart\Puli\Repository\ResourceRepository;

/**
 * @author Bernhard Schussek <bschussek@gmail.com>
 */
class ResourceRepositoryTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var ResourceRepository
     */
    private $repo;

    protected function setUp()
    {
        $this->repo = new ResourceRepository();
    }

    public function testAddFile()
    {
        $this->repo->add('/webmozart/puli/file1', __DIR__.'/Fixtures/dir1/file1');

        $file = $this->repo->get('/webmozart/puli/file1');

        $this->assertInstanceOf('Webmozart\\Puli\\Resource\\FileResource', $file);
        $this->assertEquals('/webmozart/puli/file1', $file->getRepositoryPath());
        $this->assertEquals(__DIR__.'/Fixtures/dir1/file1', $file->getPath());
        $this->assertEquals(array(), $file->getAlternativePaths());
    }

    public function testAddDirectory()
    {
        $this->repo->add('/webmozart/puli', __DIR__.'/Fixtures/dir1');

        $dir = $this->repo->get('/webmozart/puli');

        $this->assertInstanceOf('Webmozart\\Puli\\Resource\\DirectoryResource', $dir);
        $this->assertEquals('/webmozart/puli', $dir->getRepositoryPath());
        $this->assertEquals(__DIR__.'/Fixtures/dir1', $dir->getPath());
        $this->assertEquals(array(), $dir->getAlternativePaths());

        $file1 = $this->repo->get('/webmozart/puli/file1');

        $this->assertInstanceOf('Webmozart\\Puli\\Resource\\FileResource', $file1);
        $this->assertEquals('/webmozart/puli/file1', $file1->getRepositoryPath());
        $this->assertEquals(__DIR__.'/Fixtures/dir1/file1', $file1->getPath());
        $this->assertEquals(array(), $file1->getAlternativePaths());

        $file2 = $this->repo->get('/webmozart/puli/file2');

        $this->assertInstanceOf('Webmozart\\Puli\\Resource\\FileResource', $file2);
        $this->assertEquals('/webmozart/puli/file2', $file2->getRepositoryPath());
        $this->assertEquals(__DIR__.'/Fixtures/dir1/file2', $file2->getPath());
        $this->assertEquals(array(), $file2->getAlternativePaths());
    }

    public function testAddArray()
    {
        $this->repo->add('/webmozart/puli', array(
            __DIR__.'/Fixtures/dir1/file2',
            __DIR__.'/Fixtures/dir2/file1',
        ));

        $file1 = $this->repo->get('/webmozart/puli/file1');

        $this->assertInstanceOf('Webmozart\\Puli\\Resource\\FileResource', $file1);
        $this->assertEquals('/webmozart/puli/file1', $file1->getRepositoryPath());
        $this->assertEquals(__DIR__.'/Fixtures/dir2/file1', $file1->getPath());
        $this->assertEquals(array(), $file1->getAlternativePaths());

        $file2 = $this->repo->get('/webmozart/puli/file2');

        $this->assertInstanceOf('Webmozart\\Puli\\Resource\\FileResource', $file2);
        $this->assertEquals('/webmozart/puli/file2', $file2->getRepositoryPath());
        $this->assertEquals(__DIR__.'/Fixtures/dir1/file2', $file2->getPath());
        $this->assertEquals(array(), $file2->getAlternativePaths());
    }

    public function testAddPattern()
    {
        $this->repo->add('/webmozart/puli', __DIR__.'/Fixtures/dir1/*');

        $file1 = $this->repo->get('/webmozart/puli/file1');

        $this->assertInstanceOf('Webmozart\\Puli\\Resource\\FileResource', $file1);
        $this->assertEquals('/webmozart/puli/file1', $file1->getRepositoryPath());
        $this->assertEquals(__DIR__.'/Fixtures/dir1/file1', $file1->getPath());
        $this->assertEquals(array(), $file1->getAlternativePaths());

        $file2 = $this->repo->get('/webmozart/puli/file2');

        $this->assertInstanceOf('Webmozart\\Puli\\Resource\\FileResource', $file2);
        $this->assertEquals('/webmozart/puli/file2', $file2->getRepositoryPath());
        $this->assertEquals(__DIR__.'/Fixtures/dir1/file2', $file2->getPath());
        $this->assertEquals(array(), $file2->getAlternativePaths());
    }

    public function testAddPatternInstance()
    {
        $this->repo->add('/webmozart/puli', new GlobPattern(__DIR__.'/Fixtures/dir1/*'));

        $file1 = $this->repo->get('/webmozart/puli/file1');

        $this->assertInstanceOf('Webmozart\\Puli\\Resource\\FileResource', $file1);
        $this->assertEquals('/webmozart/puli/file1', $file1->getRepositoryPath());
        $this->assertEquals(__DIR__.'/Fixtures/dir1/file1', $file1->getPath());
        $this->assertEquals(array(), $file1->getAlternativePaths());

        $file2 = $this->repo->get('/webmozart/puli/file2');

        $this->assertInstanceOf('Webmozart\\Puli\\Resource\\FileResource', $file2);
        $this->assertEquals('/webmozart/puli/file2', $file2->getRepositoryPath());
        $this->assertEquals(__DIR__.'/Fixtures/dir1/file2', $file2->getPath());
        $this->assertEquals(array(), $file2->getAlternativePaths());
    }

    public function testAddArrayPattern()
    {
        $this->repo->add('/webmozart/puli', array(
            __DIR__.'/Fixtures/dir1/file2',
            __DIR__.'/Fixtures/dir2/*',
        ));

        $file1 = $this->repo->get('/webmozart/puli/file1');

        $this->assertInstanceOf('Webmozart\\Puli\\Resource\\FileResource', $file1);
        $this->assertEquals('/webmozart/puli/file1', $file1->getRepositoryPath());
        $this->assertEquals(__DIR__.'/Fixtures/dir2/file1', $file1->getPath());
        $this->assertEquals(array(), $file1->getAlternativePaths());

        $file2 = $this->repo->get('/webmozart/puli/file2');

        $this->assertInstanceOf('Webmozart\\Puli\\Resource\\FileResource', $file2);
        $this->assertEquals('/webmozart/puli/file2', $file2->getRepositoryPath());
        $this->assertEquals(__DIR__.'/Fixtures/dir1/file2', $file2->getPath());
        $this->assertEquals(array(), $file2->getAlternativePaths());
    }

    /**
     * @expectedException \InvalidArgumentException
     */
    public function testAddExpectsStringOrArray()
    {
        $this->repo->add('/webmozart/puli', 12345);
    }

    public function testAddTrimsTrailingSlash()
    {
        $this->repo->add('/webmozart/puli/', __DIR__.'/Fixtures/dir1');

        $dir = $this->repo->get('/webmozart/puli');

        $this->assertInstanceOf('Webmozart\\Puli\\Resource\\DirectoryResource', $dir);
        $this->assertEquals('/webmozart/puli', $dir->getRepositoryPath());
        $this->assertEquals(__DIR__.'/Fixtures/dir1', $dir->getPath());
        $this->assertEquals(array(), $dir->getAlternativePaths());
    }

    public function testOverrideFile()
    {
        $this->repo->add('/webmozart/puli/file1', __DIR__.'/Fixtures/dir1/file1');
        $this->repo->add('/webmozart/puli/file1', __DIR__.'/Fixtures/dir1/file2');

        $file = $this->repo->get('/webmozart/puli/file1');

        $this->assertInstanceOf('Webmozart\\Puli\\Resource\\FileResource', $file);
        $this->assertEquals('/webmozart/puli/file1', $file->getRepositoryPath());
        $this->assertEquals(__DIR__.'/Fixtures/dir1/file2', $file->getPath());
        $this->assertEquals(array(__DIR__.'/Fixtures/dir1/file1'), $file->getAlternativePaths());
    }

    public function testOverrideDirectory()
    {
        $this->repo->add('/webmozart/puli', __DIR__.'/Fixtures/dir1');
        $this->repo->add('/webmozart/puli', __DIR__.'/Fixtures/dir2');

        $dir = $this->repo->get('/webmozart/puli');

        $this->assertEquals('/webmozart/puli', $dir->getRepositoryPath());
        $this->assertEquals(__DIR__.'/Fixtures/dir2', $dir->getPath());
        $this->assertEquals(array(__DIR__.'/Fixtures/dir1'), $dir->getAlternativePaths());

        $file1 = $this->repo->get('/webmozart/puli/file1');

        $this->assertInstanceOf('Webmozart\\Puli\\Resource\\FileResource', $file1);
        $this->assertEquals('/webmozart/puli/file1', $file1->getRepositoryPath());
        $this->assertEquals(__DIR__.'/Fixtures/dir2/file1', $file1->getPath());
        $this->assertEquals(array(__DIR__.'/Fixtures/dir1/file1'), $file1->getAlternativePaths());

        $file2 = $this->repo->get('/webmozart/puli/file2');

        $this->assertInstanceOf('Webmozart\\Puli\\Resource\\FileResource', $file2);
        $this->assertEquals('/webmozart/puli/file2', $file2->getRepositoryPath());
        $this->assertEquals(__DIR__.'/Fixtures/dir1/file2', $file2->getPath());
        $this->assertEquals(array(), $file2->getAlternativePaths());
    }

    public function testContainsPath()
    {
        $this->assertTrue($this->repo->contains('/'));
        $this->assertFalse($this->repo->contains('/webmozart'));
        $this->assertFalse($this->repo->contains('/webmozart/puli'));
        $this->assertFalse($this->repo->contains('/webmozart/puli/file1'));
        $this->assertFalse($this->repo->contains('/webmozart/puli/file2'));

        $this->repo->add('/webmozart/puli', __DIR__.'/Fixtures/dir1');

        $this->assertTrue($this->repo->contains('/'));
        $this->assertTrue($this->repo->contains('/webmozart'));
        $this->assertTrue($this->repo->contains('/webmozart/puli'));
        $this->assertTrue($this->repo->contains('/webmozart/puli/file1'));
        $this->assertTrue($this->repo->contains('/webmozart/puli/file2'));
    }

    public function testContainsArray()
    {
        $this->assertFalse($this->repo->contains(array(
            '/webmozart/puli/file1',
            '/webmozart/puli/file2',
        )));
        $this->assertFalse($this->repo->contains(array(
            '/webmozart/puli/file1',
            '/webmozart/puli/file2',
            '/webmozart/puli/file3',
        )));

        $this->repo->add('/webmozart/puli', __DIR__.'/Fixtures/dir1/*');

        $this->assertTrue($this->repo->contains(array(
            '/webmozart/puli/file1',
            '/webmozart/puli/file2',
        )));
        $this->assertFalse($this->repo->contains(array(
            '/webmozart/puli/file1',
            '/webmozart/puli/file2',
            '/webmozart/puli/file3',
        )));
    }

    public function testContainsPattern()
    {
        $this->assertFalse($this->repo->contains('/webmozart/*'));
        $this->assertFalse($this->repo->contains('/webmozart/file*'));
        $this->assertFalse($this->repo->contains('/webmozart/puli/file*'));
        $this->assertFalse($this->repo->contains('/webmozart/*/file*'));

        $this->repo->add('/webmozart/puli', __DIR__.'/Fixtures/dir1');

        $this->assertTrue($this->repo->contains('/webmozart/*'));
        $this->assertFalse($this->repo->contains('/webmozart/file*'));
        $this->assertTrue($this->repo->contains('/webmozart/puli/file*'));
        $this->assertTrue($this->repo->contains('/webmozart/*/file*'));
    }

    public function testContainsPatternInstance()
    {
        $this->assertFalse($this->repo->contains(new GlobPattern('/webmozart/*')));
        $this->assertFalse($this->repo->contains(new GlobPattern('/webmozart/file*')));
        $this->assertFalse($this->repo->contains(new GlobPattern('/webmozart/puli/file*')));
        $this->assertFalse($this->repo->contains(new GlobPattern('/webmozart/*/file*')));

        $this->repo->add('/webmozart/puli', __DIR__.'/Fixtures/dir1');

        $this->assertTrue($this->repo->contains(new GlobPattern('/webmozart/*')));
        $this->assertFalse($this->repo->contains(new GlobPattern('/webmozart/file*')));
        $this->assertTrue($this->repo->contains(new GlobPattern('/webmozart/puli/file*')));
        $this->assertTrue($this->repo->contains(new GlobPattern('/webmozart/*/file*')));
    }

    public function testContainsArrayPattern()
    {
        $this->assertFalse($this->repo->contains(array(
            '/webmozart/puli/file1',
            '/webmozart/puli/*2',
        )));

        $this->repo->add('/webmozart/puli', __DIR__.'/Fixtures/dir1');

        $this->assertTrue($this->repo->contains(array(
            '/webmozart/puli/file1',
            '/webmozart/puli/*2',
        )));
    }

    public function testContainsDiscardsTrailingSlash()
    {
        $this->repo->add('/webmozart/puli', __DIR__.'/Fixtures/dir1');

        $this->assertTrue($this->repo->contains('/webmozart/puli/'));
    }

    public function testContainsInterpretsConsecutiveSlashesAsRoot()
    {
        $this->assertTrue($this->repo->contains('///'));
    }

    public function testRemoveOne()
    {
        $this->repo->add('/webmozart/puli', __DIR__.'/Fixtures/dir1');

        $this->assertTrue($this->repo->contains('/webmozart/puli'));
        $this->assertTrue($this->repo->contains('/webmozart/puli/file1'));
        $this->assertTrue($this->repo->contains('/webmozart/puli/file2'));

        $this->repo->remove('/webmozart/puli/file2');

        $this->assertTrue($this->repo->contains('/webmozart/puli'));
        $this->assertTrue($this->repo->contains('/webmozart/puli/file1'));
        $this->assertFalse($this->repo->contains('/webmozart/puli/file2'));
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

    public function provideDirectorySelector()
    {
        return array(
            array('/webmozart/puli'),
            array('/webmozart/pu*'),
            array(new GlobPattern('/webmozart/pu*')),
            array(array(
                '/webmozart/puli',
            )),
            array(array(
                '/webmozart/pu*',
            )),
            array(array(
                new GlobPattern('/webmozart/pu*'),
            )),
        );
    }

    /**
     * @dataProvider provideManySelector
     */
    public function testRemoveMany($selector)
    {
        $this->repo->add('/webmozart/puli', __DIR__.'/Fixtures/dir1');

        $this->assertTrue($this->repo->contains('/webmozart/puli'));
        $this->assertTrue($this->repo->contains('/webmozart/puli/file1'));
        $this->assertTrue($this->repo->contains('/webmozart/puli/file2'));

        $this->repo->remove($selector);

        $this->assertTrue($this->repo->contains('/webmozart/puli'));
        $this->assertFalse($this->repo->contains('/webmozart/puli/file1'));
        $this->assertFalse($this->repo->contains('/webmozart/puli/file2'));
    }

    /**
     * @dataProvider provideDirectorySelector
     */
    public function testRemoveDirectory($selector)
    {
        $this->repo->add('/webmozart/puli', __DIR__.'/Fixtures/dir1');

        $this->assertTrue($this->repo->contains('/webmozart/puli'));
        $this->assertTrue($this->repo->contains('/webmozart/puli/file1'));
        $this->assertTrue($this->repo->contains('/webmozart/puli/file2'));

        $this->repo->remove($selector);

        $this->assertFalse($this->repo->contains('/webmozart/puli'));
        $this->assertFalse($this->repo->contains('/webmozart/puli/file1'));
        $this->assertFalse($this->repo->contains('/webmozart/puli/file2'));
    }

    public function testRemoveDiscardsTrailingSlash()
    {
        $this->repo->add('/webmozart/puli', __DIR__.'/Fixtures/dir1');

        $this->assertTrue($this->repo->contains('/webmozart/puli'));
        $this->assertTrue($this->repo->contains('/webmozart/puli/file1'));
        $this->assertTrue($this->repo->contains('/webmozart/puli/file2'));

        $this->repo->remove('/webmozart/puli/');

        $this->assertFalse($this->repo->contains('/webmozart/puli'));
        $this->assertFalse($this->repo->contains('/webmozart/puli/file1'));
        $this->assertFalse($this->repo->contains('/webmozart/puli/file2'));
    }

    /**
     * @expectedException \Webmozart\Puli\Repository\RemovalNotAllowedException
     */
    public function testRemoveDoesNotRemoveRoot()
    {
        $this->repo->remove('/');
    }

    /**
     * @expectedException \Webmozart\Puli\Repository\RemovalNotAllowedException
     */
    public function testRemoveInterpretsConsecutiveSlashesAsRoot()
    {
        $this->repo->remove('///');
    }

    /**
     * @dataProvider provideManySelector
     */
    public function testGetMany($selector)
    {
        $this->repo->add('/webmozart/puli', __DIR__.'/Fixtures/dir1');

        $files = $this->repo->get($selector);

        $this->assertCount(2, $files);

        $this->assertInstanceOf('Webmozart\\Puli\\Resource\\FileResource', $files[0]);
        $this->assertEquals('/webmozart/puli/file1', $files[0]->getRepositoryPath());
        $this->assertEquals(__DIR__.'/Fixtures/dir1/file1', $files[0]->getPath());
        $this->assertEquals(array(), $files[0]->getAlternativePaths());

        $this->assertInstanceOf('Webmozart\\Puli\\Resource\\FileResource', $files[1]);
        $this->assertEquals('/webmozart/puli/file2', $files[1]->getRepositoryPath());
        $this->assertEquals(__DIR__.'/Fixtures/dir1/file2', $files[1]->getPath());
        $this->assertEquals(array(), $files[1]->getAlternativePaths());
    }

    public function testGetDiscardsTrailingSlash()
    {
        $this->repo->add('/webmozart/puli', __DIR__.'/Fixtures/dir1');

        $dir = $this->repo->get('/webmozart/puli/');

        $this->assertInstanceOf('Webmozart\\Puli\\Resource\\DirectoryResource', $dir);
        $this->assertEquals('/webmozart/puli', $dir->getRepositoryPath());
        $this->assertEquals(__DIR__.'/Fixtures/dir1', $dir->getPath());
        $this->assertEquals(array(), $dir->getAlternativePaths());
    }

    public function testGetInterpretsConsecutiveSlashesAsRoot()
    {
        $dir = $this->repo->get('///');

        $this->assertInstanceOf('Webmozart\\Puli\\Resource\\DirectoryResource', $dir);
        $this->assertEquals('/', $dir->getRepositoryPath());
        $this->assertNull($dir->getPath());
        $this->assertEquals(array(), $dir->getAlternativePaths());
    }

    public function testGetEmptyPattern()
    {
        $this->assertEquals(array(), $this->repo->get('/foo/*'));
    }

    /**
     * @expectedException \Webmozart\Puli\Repository\ResourceNotFoundException
     */
    public function testGetExpectsValidResource()
    {
        $this->repo->get('/foo/bar');
    }

    /**
     * @expectedException \Webmozart\Puli\Repository\ResourceNotFoundException
     */
    public function testGetExpectsValidResourceArray()
    {
        $this->repo->add('/webmozart/puli', __DIR__.'/Fixtures/dir1');

        $this->repo->get(array(
            '/webmozart/puli/file1',
            '/foo/bar',
        ));
    }

    public function testListDirectory()
    {
        $resources = $this->repo->listDirectory('/');

        $this->assertCount(0, $resources);

        $this->repo->add('/webmozart/puli', __DIR__.'/Fixtures/dir1');
        $this->repo->add('/foo/bar', __DIR__.'/Fixtures/dir2');

        $resources = $this->repo->listDirectory('/');

        $this->assertCount(2, $resources);

        $this->assertInstanceOf('Webmozart\\Puli\\Resource\\DirectoryResource', $resources[0]);
        $this->assertEquals('/foo', $resources[0]->getRepositoryPath());
        $this->assertNull($resources[0]->getPath());
        $this->assertEquals(array(), $resources[0]->getAlternativePaths());

        $this->assertInstanceOf('Webmozart\\Puli\\Resource\\DirectoryResource', $resources[1]);
        $this->assertEquals('/webmozart', $resources[1]->getRepositoryPath());
        $this->assertNull($resources[1]->getPath());
        $this->assertEquals(array(), $resources[1]->getAlternativePaths());

        $resources = $this->repo->listDirectory('/webmozart');

        $this->assertCount(1, $resources);

        $this->assertInstanceOf('Webmozart\\Puli\\Resource\\DirectoryResource', $resources[0]);
        $this->assertEquals('/webmozart/puli', $resources[0]->getRepositoryPath());
        $this->assertEquals(__DIR__.'/Fixtures/dir1', $resources[0]->getPath());
        $this->assertEquals(array(), $resources[0]->getAlternativePaths());

        $resources = $this->repo->listDirectory('/webmozart/puli');

        $this->assertCount(2, $resources);

        $this->assertInstanceOf('Webmozart\\Puli\\Resource\\FileResource', $resources[0]);
        $this->assertEquals('/webmozart/puli/file1', $resources[0]->getRepositoryPath());
        $this->assertEquals(__DIR__.'/Fixtures/dir1/file1', $resources[0]->getPath());
        $this->assertEquals(array(), $resources[0]->getAlternativePaths());

        $this->assertInstanceOf('Webmozart\\Puli\\Resource\\FileResource', $resources[1]);
        $this->assertEquals('/webmozart/puli/file2', $resources[1]->getRepositoryPath());
        $this->assertEquals(__DIR__.'/Fixtures/dir1/file2', $resources[1]->getPath());
        $this->assertEquals(array(), $resources[1]->getAlternativePaths());
    }

    public function testListDirectoryDiscardsTrailingSlash()
    {
        $this->repo->add('/webmozart/puli', __DIR__.'/Fixtures/dir1');

        $resources = $this->repo->listDirectory('/webmozart/puli/');

        $this->assertCount(2, $resources);

        $this->assertInstanceOf('Webmozart\\Puli\\Resource\\FileResource', $resources[0]);
        $this->assertEquals('/webmozart/puli/file1', $resources[0]->getRepositoryPath());
        $this->assertEquals(__DIR__.'/Fixtures/dir1/file1', $resources[0]->getPath());
        $this->assertEquals(array(), $resources[0]->getAlternativePaths());

        $this->assertInstanceOf('Webmozart\\Puli\\Resource\\FileResource', $resources[1]);
        $this->assertEquals('/webmozart/puli/file2', $resources[1]->getRepositoryPath());
        $this->assertEquals(__DIR__.'/Fixtures/dir1/file2', $resources[1]->getPath());
        $this->assertEquals(array(), $resources[1]->getAlternativePaths());
    }

    /**
     * @expectedException \Webmozart\Puli\Repository\ResourceNotFoundException
     */
    public function testListDirectoryExpectsValidPath()
    {
        $this->repo->listDirectory('/foo/bar');
    }

    /**
     * @expectedException \InvalidArgumentException
     */
    public function testListDirectoryExpectsDirectory()
    {
        $this->repo->add('/webmozart/puli', __DIR__.'/Fixtures/dir1');

        $this->repo->listDirectory('/webmozart/puli/file1');
    }

    public function testListDirectoryDoesNotShowRemovedFiles()
    {
        $this->repo->add('/webmozart/puli', __DIR__.'/Fixtures/dir1');

        $this->repo->remove('/webmozart/puli/file1');

        $resources = $this->repo->listDirectory('/webmozart/puli/');

        $this->assertCount(1, $resources);

        $this->assertInstanceOf('Webmozart\\Puli\\Resource\\FileResource', $resources[0]);
        $this->assertEquals('/webmozart/puli/file2', $resources[0]->getRepositoryPath());
        $this->assertEquals(__DIR__.'/Fixtures/dir1/file2', $resources[0]->getPath());
        $this->assertEquals(array(), $resources[0]->getAlternativePaths());
    }

    public function testTagOne()
    {
        $this->repo->add('/webmozart/puli', __DIR__.'/Fixtures/dir1');

        $this->repo->tag('/webmozart/puli/file1', 'webmozart/tag');

        $results = array($this->repo->get('/webmozart/puli/file1'));

        $this->assertEquals($results, $this->repo->getByTag('webmozart/tag'));
    }

    /**
     * @dataProvider provideManySelector
     */
    public function testTagMany($selector)
    {
        $this->repo->add('/webmozart/puli', __DIR__.'/Fixtures/dir1');

        $this->repo->tag($selector, 'webmozart/tag');

        $results = array(
            $this->repo->get('/webmozart/puli/file1'),
            $this->repo->get('/webmozart/puli/file2'),
        );

        $this->assertEquals($results, $this->repo->getByTag('webmozart/tag'));
    }

    public function testTagDoesNotShowRemovedFiles()
    {
        $this->repo->add('/webmozart/puli', __DIR__.'/Fixtures/dir1');

        $this->repo->tag('/webmozart/puli/file1', 'webmozart/tag');

        $this->repo->remove('/webmozart/puli/file1');

        $this->assertEquals(array(), $this->repo->getByTag('webmozart/tag'));
    }

    /**
     * @expectedException \Webmozart\Puli\Repository\ResourceNotFoundException
     */
    public function testTagExpectsValidPath()
    {
        $this->repo->tag('/foo/bar', 'webmozart/tag');
    }

    public function testUntagOne()
    {
        $this->repo->add('/webmozart/puli', __DIR__.'/Fixtures/dir1');

        $this->repo->tag('/webmozart/puli/file1', 'webmozart/tag1');
        $this->repo->tag('/webmozart/puli/file1', 'webmozart/tag2');
        $this->repo->tag('/webmozart/puli/file2', 'webmozart/tag1');

        $this->repo->untag('/webmozart/puli/file1', 'webmozart/tag1');

        $tag1 = array($this->repo->get('/webmozart/puli/file2'));
        $tag2 = array($this->repo->get('/webmozart/puli/file1'));

        $this->assertEquals($tag1, $this->repo->getByTag('webmozart/tag1'));
        $this->assertEquals($tag2, $this->repo->getByTag('webmozart/tag2'));
    }

    public function testUntagOneIgnoresIfNotTagged()
    {
        $this->repo->add('/webmozart/puli', __DIR__.'/Fixtures/dir1');

        $this->repo->untag('/webmozart/puli/file1', 'webmozart/tag1');
    }

    public function testUntagOneAllTags()
    {
        $this->repo->add('/webmozart/puli', __DIR__.'/Fixtures/dir1');

        $this->repo->tag('/webmozart/puli/file1', 'webmozart/tag1');
        $this->repo->tag('/webmozart/puli/file1', 'webmozart/tag2');
        $this->repo->tag('/webmozart/puli/file2', 'webmozart/tag1');

        $this->repo->untag('/webmozart/puli/file1');

        $tag1 = array($this->repo->get('/webmozart/puli/file2'));
        $tag2 = array();

        $this->assertEquals($tag1, $this->repo->getByTag('webmozart/tag1'));
        $this->assertEquals($tag2, $this->repo->getByTag('webmozart/tag2'));
    }

    /**
     * @dataProvider provideManySelector
     */
    public function testUntagMany($selector)
    {
        $this->repo->add('/webmozart/puli', __DIR__.'/Fixtures/dir1');

        $this->repo->tag('/webmozart/puli/file1', 'webmozart/tag1');
        $this->repo->tag('/webmozart/puli/file1', 'webmozart/tag2');
        $this->repo->tag('/webmozart/puli/file2', 'webmozart/tag1');

        $this->repo->untag($selector, 'webmozart/tag1');

        $tag1 = array();
        $tag2 = array($this->repo->get('/webmozart/puli/file1'));

        $this->assertEquals($tag1, $this->repo->getByTag('webmozart/tag1'));
        $this->assertEquals($tag2, $this->repo->getByTag('webmozart/tag2'));
    }

    /**
     * @dataProvider provideManySelector
     */
    public function testUntagManyIgnoresIfNotTagged($selector)
    {
        $this->repo->add('/webmozart/puli', __DIR__.'/Fixtures/dir1');

        $this->repo->untag($selector, 'webmozart/tag1');
    }

    /**
     * @dataProvider provideManySelector
     */
    public function testUntagManyAllTags($selector)
    {
        $this->repo->add('/webmozart/puli', __DIR__.'/Fixtures/dir1');

        $this->repo->tag('/webmozart/puli/file1', 'webmozart/tag1');
        $this->repo->tag('/webmozart/puli/file1', 'webmozart/tag2');
        $this->repo->tag('/webmozart/puli/file2', 'webmozart/tag1');

        $this->repo->untag($selector);

        $tag1 = array();
        $tag2 = array();

        $this->assertEquals($tag1, $this->repo->getByTag('webmozart/tag1'));
        $this->assertEquals($tag2, $this->repo->getByTag('webmozart/tag2'));
    }

    /**
     * @expectedException \Webmozart\Puli\Repository\ResourceNotFoundException
     */
    public function testUntagExpectsValidPath()
    {
        $this->repo->untag('/foo/bar', 'webmozart/tag');
    }

    public function testGetByTagIgnoresNonExistingTags()
    {
        $this->assertEquals(array(), $this->repo->getByTag('foo/bar'));
    }

    public function testGetTags()
    {
        $this->repo->add('/webmozart/puli', __DIR__.'/Fixtures/dir1');

        $this->repo->tag('/webmozart/puli/file1', 'webmozart/tag1');

        $tags = $this->repo->getTags();

        $this->assertCount(1, $tags);
        $this->assertEquals('webmozart/tag1', $tags[0]->getName());

        $this->repo->tag('/webmozart/puli/file2', 'webmozart/tag2');

        $tags = $this->repo->getTags();

        $this->assertCount(2, $tags);
        $this->assertEquals('webmozart/tag1', $tags[0]->getName());
        $this->assertEquals('webmozart/tag2', $tags[1]->getName());

        $this->repo->untag('/webmozart/puli/file1', 'webmozart/tag1');

        $tags = $this->repo->getTags();

        $this->assertCount(1, $tags);
        $this->assertEquals('webmozart/tag2', $tags[0]->getName());
    }

    public function testGetTagsReturnsSortedResult()
    {
        $this->repo->add('/webmozart/puli', __DIR__.'/Fixtures/dir1');

        $this->repo->tag('/webmozart/puli/file1', 'webmozart/foo');
        $this->repo->tag('/webmozart/puli/file1', 'webmozart/bar');

        $tags = $this->repo->getTags();

        $this->assertCount(2, $tags);
        $this->assertEquals('webmozart/bar', $tags[0]->getName());
        $this->assertEquals('webmozart/foo', $tags[1]->getName());

    }
}
