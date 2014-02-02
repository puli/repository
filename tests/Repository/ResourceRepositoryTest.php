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
        $this->assertEquals(array(__DIR__.'/Fixtures/dir1'), $dir->getPaths());

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
        $this->assertEquals(array(__DIR__.'/Fixtures/dir1', __DIR__.'/Fixtures/dir2'), $dir->getPaths());

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
        $this->assertFalse($this->repo->contains('/'));
        $this->assertFalse($this->repo->contains('/webmozart'));
        $this->assertFalse($this->repo->contains('/webmozart/puli'));
        $this->assertFalse($this->repo->contains('/webmozart/puli/file1'));
        $this->assertFalse($this->repo->contains('/webmozart/puli/file2'));

        $this->repo->add('/webmozart/puli', __DIR__.'/Fixtures/dir1');

        $this->assertFalse($this->repo->contains('/'));
        $this->assertFalse($this->repo->contains('/webmozart'));
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

    public function testRemovePath()
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

    public function testRemovePattern()
    {
        $this->repo->add('/webmozart/puli', __DIR__.'/Fixtures/dir1');

        $this->assertTrue($this->repo->contains('/webmozart/puli'));
        $this->assertTrue($this->repo->contains('/webmozart/puli/file1'));
        $this->assertTrue($this->repo->contains('/webmozart/puli/file2'));

        $this->repo->remove('/webmozart/puli/*');

        $this->assertTrue($this->repo->contains('/webmozart/puli'));
        $this->assertFalse($this->repo->contains('/webmozart/puli/file1'));
        $this->assertFalse($this->repo->contains('/webmozart/puli/file2'));
    }

    public function testRemovePatternInstance()
    {
        $this->repo->add('/webmozart/puli', __DIR__.'/Fixtures/dir1');

        $this->assertTrue($this->repo->contains('/webmozart/puli'));
        $this->assertTrue($this->repo->contains('/webmozart/puli/file1'));
        $this->assertTrue($this->repo->contains('/webmozart/puli/file2'));

        $this->repo->remove(new GlobPattern('/webmozart/puli/*'));

        $this->assertTrue($this->repo->contains('/webmozart/puli'));
        $this->assertFalse($this->repo->contains('/webmozart/puli/file1'));
        $this->assertFalse($this->repo->contains('/webmozart/puli/file2'));
    }

    public function testRemoveArray()
    {
        $this->repo->add('/webmozart/puli', __DIR__.'/Fixtures/dir1');

        $this->assertTrue($this->repo->contains('/webmozart/puli'));
        $this->assertTrue($this->repo->contains('/webmozart/puli/file1'));
        $this->assertTrue($this->repo->contains('/webmozart/puli/file2'));

        $this->repo->remove(array(
            '/webmozart/puli/file1',
            '/webmozart/puli/file2',
        ));

        $this->assertTrue($this->repo->contains('/webmozart/puli'));
        $this->assertFalse($this->repo->contains('/webmozart/puli/file1'));
        $this->assertFalse($this->repo->contains('/webmozart/puli/file2'));
    }

    public function testRemoveArrayPattern()
    {
        $this->repo->add('/webmozart/puli', __DIR__.'/Fixtures/dir1');

        $this->assertTrue($this->repo->contains('/webmozart/puli'));
        $this->assertTrue($this->repo->contains('/webmozart/puli/file1'));
        $this->assertTrue($this->repo->contains('/webmozart/puli/file2'));

        $this->repo->remove(array(
            '/webmozart/puli/file1',
            '/webmozart/puli/*2',
        ));

        $this->assertTrue($this->repo->contains('/webmozart/puli'));
        $this->assertFalse($this->repo->contains('/webmozart/puli/file1'));
        $this->assertFalse($this->repo->contains('/webmozart/puli/file2'));
    }

    /**
     * @expectedException \Webmozart\Puli\Repository\ResourceNotFoundException
     */
    public function testGetExpectsValidResource()
    {
        $this->repo->get('/foo/bar');
    }
}
