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

use Puli\Repository\InMemoryRepository;
use Puli\Repository\Resource\Collection\ArrayResourceCollection;
use Puli\Repository\Resource\DirectoryResource;
use Puli\Repository\Tests\Resource\TestDirectory;
use Puli\Repository\Tests\Resource\TestFile;

/**
 * @since  1.0
 * @author Bernhard Schussek <bschussek@gmail.com>
 */
class InMemoryRepositoryTest extends AbstractRepositoryTest
{
    /**
     * @var InMemoryRepository
     */
    protected $repo;

    protected function setUp()
    {
        parent::setUp();

        $this->repo = new InMemoryRepository();
    }

    protected function createRepository(DirectoryResource $root)
    {
        $repo = new InMemoryRepository();
        $repo->add('/', $root);

        return $repo;
    }

    protected function assertSameResource($expected, $actual)
    {
        $this->assertSame($expected, $actual);
    }

    public function testGetFile()
    {
        $file = new TestFile();

        $this->repo->add('/webmozart/puli/file1', $file);

        $this->assertSame($file, $this->repo->get('/webmozart/puli/file1'));
        $this->assertSame('/webmozart/puli/file1', $file->getPath());
        $this->assertSame($this->repo, $file->getRepository());
    }

    public function testGetDirectory()
    {
        $dir = new TestDirectory('/path', array(
            $file1 = new TestFile('/path/file1'),
            $file2 = new TestFile('/path/file2'),
        ));

        $this->repo->add('/webmozart/puli', $dir);

        $this->assertSame($dir, $this->repo->get('/webmozart/puli'));
        $this->assertCount(2, $dir->listEntries());
        $this->assertSame('/webmozart/puli', $dir->getPath());
        $this->assertSame($this->repo, $dir->getRepository());

        $this->assertSame($file1, $this->repo->get('/webmozart/puli/file1'));
        $this->assertSame('/webmozart/puli/file1', $file1->getPath());
        $this->assertSame($this->repo, $file1->getRepository());

        $this->assertSame($file2, $this->repo->get('/webmozart/puli/file2'));
        $this->assertSame('/webmozart/puli/file2', $file2->getPath());
        $this->assertSame($this->repo, $file2->getRepository());
    }

    public function testGetRootBeforeAdding()
    {
        $repo = new InMemoryRepository();
        $root = $repo->get('/');

        $this->assertInstanceOf('Puli\Repository\Resource\DirectoryResource', $root);
        $this->assertCount(0, $root->listEntries());
        $this->assertSame('/', $root->getPath());
    }

    public function testGetOverriddenFile()
    {
        $file1 = new TestFile();
        $file2 = new TestFile();

        $this->repo->add('/webmozart/puli/file1', $file1);
        $this->repo->add('/webmozart/puli/file1', $file2);

        $this->assertSame($file2, $this->repo->get('/webmozart/puli/file1'));
        $this->assertSame($file1, $file2->getOverriddenResource());
        $this->assertSame('/webmozart/puli/file1', $file2->getPath());
        $this->assertSame($this->repo, $file2->getRepository());
    }

    public function testGetOverriddenDirectory()
    {
        $dir1 = new TestDirectory('/path', array(
            $file11 = new TestFile('/path/file1'),
            $file12 = new TestFile('/path/file2'),
        ));
        $dir2 = new TestDirectory('/path', array(
            $file22 = new TestFile('/path/file2'),
        ));

        $this->repo->add('/webmozart/puli', $dir1);
        $this->repo->add('/webmozart/puli', $dir2);

        $this->assertSame($dir2, $this->repo->get('/webmozart/puli'));
        $this->assertSame($dir1, $dir2->getOverriddenResource());
        $this->assertSame('/webmozart/puli', $dir2->getPath());
        $this->assertSame($this->repo, $dir2->getRepository());

        $this->assertSame($file11, $this->repo->get('/webmozart/puli/file1'));
        $this->assertNull($file11->getOverriddenResource());
        $this->assertSame('/webmozart/puli/file1', $file11->getPath());
        $this->assertSame($this->repo, $file11->getRepository());

        $this->assertSame($file22, $this->repo->get('/webmozart/puli/file2'));
        $this->assertSame($file12, $file22->getOverriddenResource());
        $this->assertSame('/webmozart/puli/file2', $file22->getPath());
        $this->assertSame($this->repo, $file22->getRepository());
    }

    /**
     * @expectedException \Puli\Repository\NoDirectoryException
     */
    public function testAddFileAsChildOfFile()
    {
        $file1 = new TestFile();
        $file2 = new TestFile();

        $this->repo->add('/webmozart/puli', $file1);
        $this->repo->add('/webmozart/puli/file', $file2);
    }

    public function testAddFileAsChildOfDirectory()
    {
        $dir = new TestDirectory();
        $file = new TestFile();

        $this->repo->add('/webmozart/puli', $dir);
        $this->repo->add('/webmozart/puli/file', $file);

        $this->assertSame($dir, $this->repo->get('/webmozart/puli'));
        $this->assertSame($file, $this->repo->get('/webmozart/puli/file'));
    }

    public function testAddDot()
    {
        $file = new TestFile();

        $this->repo->add('/webmozart/puli/file1/.', $file);

        $this->assertSame($file, $this->repo->get('/webmozart/puli/file1'));
    }

    public function testAddDotDot()
    {
        $file = new TestFile();

        $this->repo->add('/webmozart/puli/file1/..', $file);

        $this->assertSame($file, $this->repo->get('/webmozart/puli'));
    }

    public function testAddTrimsTrailingSlash()
    {
        $file = new TestFile();

        $this->repo->add('/webmozart/puli/', $file);

        $this->assertSame($file, $this->repo->get('/webmozart/puli'));
    }

    public function testAddCollection()
    {
        $file1 = new TestFile('/file1');
        $file2 = new TestFile('/file2');

        $this->repo->add('/webmozart/puli', new ArrayResourceCollection(array($file1, $file2)));

        $this->assertSame($file1, $this->repo->get('/webmozart/puli/file1'));
        $this->assertSame($file2, $this->repo->get('/webmozart/puli/file2'));
    }

    public function testAddClonesResourcesAttachedToAnotherRepository()
    {
        $otherRepo = $this->getMock('Puli\Repository\ResourceRepository');

        $file = new TestFile('/file');
        $file->attachTo($otherRepo);

        $this->repo->add('/webmozart/puli/file', $file);

        $this->assertNotSame($file, $this->repo->get('/webmozart/puli/file'));
        $this->assertSame('/file', $file->getPath());

        $clone = clone $file;
        $clone->attachTo($this->repo, '/webmozart/puli/file');

        $this->assertEquals($clone, $this->repo->get('/webmozart/puli/file'));
    }

    public function testAddCollectionClonesEntriesAttachedToAnotherRepository()
    {
        $otherRepo = $this->getMock('Puli\Repository\ResourceRepository');

        $file1 = new TestFile('/file1');
        $file2 = new TestFile('/file2');

        $file2->attachTo($otherRepo);

        $this->repo->add('/webmozart/puli', new ArrayResourceCollection(array($file1, $file2)));

        $this->assertSame($file1, $this->repo->get('/webmozart/puli/file1'));
        $this->assertNotSame($file2, $this->repo->get('/webmozart/puli/file2'));
        $this->assertSame('/file2', $file2->getPath());

        $clone = clone $file2;
        $clone->attachTo($this->repo, '/webmozart/puli/file2');

        $this->assertEquals($clone, $this->repo->get('/webmozart/puli/file2'));
    }

    public function testAddPathFromBackend()
    {
        $backend = $this->getMock('Puli\Repository\ResourceRepository');
        $file = new TestFile();
        $file->attachTo($backend, '/dir1/file1');

        $backend->expects($this->once())
            ->method('get')
            ->with('/dir1/file1')
            ->will($this->returnValue($file));

        $repo = new InMemoryRepository($backend);
        $repo->add('/webmozart/puli/file1', '/dir1/file1');

        // Backend resource was not modified
        $this->assertSame('/dir1/file1', $file->getPath());
        $this->assertSame($backend, $file->getRepository());

        $clone = clone $file;
        $clone->attachTo($repo, '/webmozart/puli/file1');
        $this->assertEquals($clone, $repo->get('/webmozart/puli/file1'));
    }

    public function testAddSelectorFromBackendManyMatches()
    {
        $backend = $this->getMock('Puli\Repository\ResourceRepository');
        $file1 = new TestFile();
        $file1->attachTo($backend, '/dir1/file1');
        $file2 = new TestFile();
        $file2->attachTo($backend, '/dir1/file2');

        $backend->expects($this->once())
            ->method('find')
            ->with('/dir1/*')
            ->will($this->returnValue(new ArrayResourceCollection(array($file1, $file2))));

        $repo = new InMemoryRepository($backend);
        $repo->add('/webmozart/puli', '/dir1/*');

        // Backend resources were not modified
        $this->assertSame('/dir1/file1', $file1->getPath());
        $this->assertSame($backend, $file1->getRepository());
        $this->assertSame('/dir1/file2', $file2->getPath());
        $this->assertSame($backend, $file2->getRepository());

        $clone1 = clone $file1;
        $clone1->attachTo($repo, '/webmozart/puli/file1');
        $this->assertEquals($clone1, $repo->get('/webmozart/puli/file1'));

        $clone2 = clone $file2;
        $clone2->attachTo($repo, '/webmozart/puli/file2');
        $this->assertEquals($clone2, $repo->get('/webmozart/puli/file2'));
    }

    public function testAddSelectorFromBackendOneMatch()
    {
        $backend = $this->getMock('Puli\Repository\ResourceRepository');
        $file1 = new TestFile();
        $file1->attachTo($backend, '/dir1/file1');

        $backend->expects($this->once())
            ->method('find')
            ->with('/dir1/*')
            ->will($this->returnValue(new ArrayResourceCollection(array($file1))));

        $repo = new InMemoryRepository($backend);
        $repo->add('/webmozart/puli', '/dir1/*');

        // Backend resources were not modified
        $this->assertSame('/dir1/file1', $file1->getPath());
        $this->assertSame($backend, $file1->getRepository());

        $clone1 = clone $file1;
        $clone1->attachTo($repo, '/webmozart/puli/file1');
        $this->assertEquals($clone1, $repo->get('/webmozart/puli/file1'));
    }

    public function testAddRoot()
    {
        $this->repo->add('/', $dir = new TestDirectory('/', array(
            $dir1 = new TestDirectory('/webmozart', array(
                $file11 = new TestFile('/webmozart/file'),
            )),
        )));

        $this->assertSame($dir, $this->repo->get('/'));
        $this->assertCount(1, $dir->listEntries());
        $this->assertSame('/', $dir->getPath());
        $this->assertSame($this->repo, $dir->getRepository());

        $this->assertSame($dir1, $this->repo->get('/webmozart'));
        $this->assertSame('/webmozart', $dir1->getPath());
        $this->assertSame($this->repo, $dir1->getRepository());

        $this->assertSame($file11, $this->repo->get('/webmozart/file'));
        $this->assertSame('/webmozart/file', $file11->getPath());
        $this->assertSame($this->repo, $file11->getRepository());
    }

    /**
     * @expectedException \InvalidArgumentException
     */
    public function testAddExpectsAbsolutePath()
    {
        $this->repo->add('webmozart', new TestDirectory());
    }

    /**
     * @expectedException \InvalidArgumentException
     */
    public function testAddExpectsNonEmptyPath()
    {
        $this->repo->add('', new TestDirectory());
    }

    /**
     * @expectedException \InvalidArgumentException
     */
    public function testAddExpectsStringPath()
    {
        $this->repo->add(new \stdClass(), new TestDirectory());
    }

    /**
     * @expectedException \Puli\Repository\UnsupportedResourceException
     */
    public function testAddExpectsResource()
    {
        $this->repo->add('/webmozart', new \stdClass());
    }

    public function testRemoveFile()
    {
        $this->repo->add('/webmozart/puli/file1', new TestFile());
        $this->repo->add('/webmozart/puli/file2', new TestFile());

        $this->assertTrue($this->repo->contains('/webmozart'));
        $this->assertTrue($this->repo->contains('/webmozart/puli'));
        $this->assertTrue($this->repo->contains('/webmozart/puli/file1'));
        $this->assertTrue($this->repo->contains('/webmozart/puli/file2'));

        $this->assertSame(1, $this->repo->remove('/webmozart/puli/file1'));

        $this->assertTrue($this->repo->contains('/webmozart'));
        $this->assertTrue($this->repo->contains('/webmozart/puli'));
        $this->assertFalse($this->repo->contains('/webmozart/puli/file1'));
        $this->assertTrue($this->repo->contains('/webmozart/puli/file2'));
    }

    public function testRemoveMany()
    {
        $this->repo->add('/webmozart/puli/file1', new TestFile());
        $this->repo->add('/webmozart/puli/file2', new TestFile());

        $this->assertTrue($this->repo->contains('/webmozart/puli'));
        $this->assertTrue($this->repo->contains('/webmozart/puli/file1'));
        $this->assertTrue($this->repo->contains('/webmozart/puli/file2'));

        $this->assertSame(2, $this->repo->remove('/webmozart/puli/file*'));

        $this->assertTrue($this->repo->contains('/webmozart/puli'));
        $this->assertFalse($this->repo->contains('/webmozart/puli/file1'));
        $this->assertFalse($this->repo->contains('/webmozart/puli/file2'));
    }

    public function provideDirectorySelector()
    {
        return array(
            array('/webmozart/puli'),
            array('/webmozart/pu*'),
        );
    }

    /**
     * @dataProvider provideDirectorySelector
     */
    public function testRemoveDirectory($selector)
    {
        $this->repo->add('/webmozart/puli/file1', new TestFile());
        $this->repo->add('/webmozart/puli/file2', new TestFile());

        $this->assertTrue($this->repo->contains('/webmozart'));
        $this->assertTrue($this->repo->contains('/webmozart/puli'));
        $this->assertTrue($this->repo->contains('/webmozart/puli/file1'));
        $this->assertTrue($this->repo->contains('/webmozart/puli/file2'));

        $this->repo->remove($selector);

        $this->assertTrue($this->repo->contains('/webmozart'));
        $this->assertFalse($this->repo->contains('/webmozart/puli'));
        $this->assertFalse($this->repo->contains('/webmozart/puli/file1'));
        $this->assertFalse($this->repo->contains('/webmozart/puli/file2'));
    }

    public function testRemoveDot()
    {
        $this->repo->add('/webmozart/puli/file1', new TestFile());
        $this->repo->add('/webmozart/puli/file2', new TestFile());

        $this->assertTrue($this->repo->contains('/webmozart'));
        $this->assertTrue($this->repo->contains('/webmozart/puli'));
        $this->assertTrue($this->repo->contains('/webmozart/puli/file1'));
        $this->assertTrue($this->repo->contains('/webmozart/puli/file2'));

        $this->repo->remove('/webmozart/puli/.');

        $this->assertTrue($this->repo->contains('/webmozart'));
        $this->assertFalse($this->repo->contains('/webmozart/puli'));
        $this->assertFalse($this->repo->contains('/webmozart/puli/file1'));
        $this->assertFalse($this->repo->contains('/webmozart/puli/file2'));
    }

    public function testRemoveDotDot()
    {
        $this->repo->add('/webmozart/puli/file1', new TestFile());
        $this->repo->add('/webmozart/puli/file2', new TestFile());

        $this->assertTrue($this->repo->contains('/'));
        $this->assertTrue($this->repo->contains('/webmozart'));
        $this->assertTrue($this->repo->contains('/webmozart/puli'));
        $this->assertTrue($this->repo->contains('/webmozart/puli/file1'));
        $this->assertTrue($this->repo->contains('/webmozart/puli/file2'));

        $this->repo->remove('/webmozart/puli/..');

        $this->assertTrue($this->repo->contains('/'));
        $this->assertFalse($this->repo->contains('/webmozart'));
        $this->assertFalse($this->repo->contains('/webmozart/puli'));
        $this->assertFalse($this->repo->contains('/webmozart/puli/file1'));
        $this->assertFalse($this->repo->contains('/webmozart/puli/file2'));
    }

    public function testRemoveDiscardsTrailingSlash()
    {
        $this->repo->add('/webmozart/puli/file1', new TestFile());
        $this->repo->add('/webmozart/puli/file2', new TestFile());

        $this->assertTrue($this->repo->contains('/webmozart/puli'));
        $this->assertTrue($this->repo->contains('/webmozart/puli/file1'));
        $this->assertTrue($this->repo->contains('/webmozart/puli/file2'));

        $this->repo->remove('/webmozart/puli/');

        $this->assertFalse($this->repo->contains('/webmozart/puli'));
        $this->assertFalse($this->repo->contains('/webmozart/puli/file1'));
        $this->assertFalse($this->repo->contains('/webmozart/puli/file2'));
    }

    public function testRemoveRoot()
    {
        $this->repo->remove('/');

        $this->assertFalse($this->repo->contains('/'));
    }

    public function testRemoveInterpretsConsecutiveSlashesAsRoot()
    {
        $this->repo->remove('/');

        $this->assertFalse($this->repo->contains('///'));
    }

    /**
     * @expectedException \InvalidArgumentException
     */
    public function testRemoveExpectsAbsolutePath()
    {
        $this->repo->remove('webmozart');
    }

    /**
     * @expectedException \InvalidArgumentException
     */
    public function testRemoveExpectsNonEmptyPath()
    {
        $this->repo->remove('');
    }

    /**
     * @expectedException \InvalidArgumentException
     */
    public function testRemoveExpectsStringPath()
    {
        $this->repo->remove(new \stdClass());
    }
}
