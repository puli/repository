<?php

/*
 * This file is part of the Puli package.
 *
 * (c) Bernhard Schussek <bschussek@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Puli\Tests;

use Puli\Resource\Collection\ResourceCollection;
use Puli\Resource\DirectoryResourceInterface;
use Puli\ResourceRepository;
use Puli\Tests\Resource\TestDirectory;
use Puli\Tests\Resource\TestFile;

/**
 * @since  1.0
 * @author Bernhard Schussek <bschussek@gmail.com>
 */
class ResourceRepositoryTest extends AbstractRepositoryTest
{
    /**
     * @var ResourceRepository
     */
    protected $repo;

    protected function setUp()
    {
        parent::setUp();

        $this->repo = new ResourceRepository();
    }

    protected function createRepository(DirectoryResourceInterface $root, array $tags = array())
    {
        $repo = new ResourceRepository();
        $repo->add('/', $root);

        foreach ($tags as $path => $tag) {
            $repo->tag($path, $tag);
        }

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
        $this->assertSame($this->repo, $file->getAttachedRepository());
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
        $this->assertSame($this->repo, $dir->getAttachedRepository());

        $this->assertSame($file1, $this->repo->get('/webmozart/puli/file1'));
        $this->assertSame('/webmozart/puli/file1', $file1->getPath());
        $this->assertSame($this->repo, $file1->getAttachedRepository());

        $this->assertSame($file2, $this->repo->get('/webmozart/puli/file2'));
        $this->assertSame('/webmozart/puli/file2', $file2->getPath());
        $this->assertSame($this->repo, $file2->getAttachedRepository());
    }

    public function testGetRootBeforeAdding()
    {
        $repo = new ResourceRepository();
        $root = $repo->get('/');

        $this->assertInstanceOf('Puli\Resource\DirectoryResourceInterface', $root);
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
        $this->assertSame($this->repo, $file2->getAttachedRepository());
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
        $this->assertSame($this->repo, $dir2->getAttachedRepository());

        $this->assertSame($file11, $this->repo->get('/webmozart/puli/file1'));
        $this->assertNull($file11->getOverriddenResource());
        $this->assertSame('/webmozart/puli/file1', $file11->getPath());
        $this->assertSame($this->repo, $file11->getAttachedRepository());

        $this->assertSame($file22, $this->repo->get('/webmozart/puli/file2'));
        $this->assertSame($file12, $file22->getOverriddenResource());
        $this->assertSame('/webmozart/puli/file2', $file22->getPath());
        $this->assertSame($this->repo, $file22->getAttachedRepository());
    }

    /**
     * @expectedException \Puli\Resource\NoDirectoryException
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

        $this->repo->add('/webmozart/puli', new ResourceCollection(array($file1, $file2)));

        $this->assertSame($file1, $this->repo->get('/webmozart/puli/file1'));
        $this->assertSame($file2, $this->repo->get('/webmozart/puli/file2'));
    }

    public function testAddOneFromBackend()
    {
        $backend = $this->getMock('Puli\ResourceRepositoryInterface');
        $file = new TestFile();
        $file->attachTo($backend, '/dir1/file1');

        $backend->expects($this->once())
            ->method('find')
            ->with('/dir1/file1')
            ->will($this->returnValue(new ResourceCollection(array($file))));

        $repo = new ResourceRepository($backend);
        $repo->add('/webmozart/puli/file1', '/dir1/file1');

        // Backend resource was not modified
        $this->assertSame('/dir1/file1', $file->getPath());
        $this->assertSame($backend, $file->getAttachedRepository());

        $clone = clone $file;
        $clone->attachTo($repo, '/webmozart/puli/file1');
        $this->assertEquals($clone, $repo->get('/webmozart/puli/file1'));
    }

    public function testAddManyFromBackend()
    {
        $backend = $this->getMock('Puli\ResourceRepositoryInterface');
        $file1 = new TestFile();
        $file1->attachTo($backend, '/dir1/file1');
        $file2 = new TestFile();
        $file2->attachTo($backend, '/dir1/file2');

        $backend->expects($this->once())
            ->method('find')
            ->with('/dir1/*')
            ->will($this->returnValue(new ResourceCollection(array($file1, $file2))));

        $repo = new ResourceRepository($backend);
        $repo->add('/webmozart/puli', '/dir1/*');

        // Backend resources were not modified
        $this->assertSame('/dir1/file1', $file1->getPath());
        $this->assertSame($backend, $file1->getAttachedRepository());
        $this->assertSame('/dir1/file2', $file2->getPath());
        $this->assertSame($backend, $file2->getAttachedRepository());

        $clone1 = clone $file1;
        $clone1->attachTo($repo, '/webmozart/puli/file1');
        $this->assertEquals($clone1, $repo->get('/webmozart/puli/file1'));

        $clone2 = clone $file2;
        $clone2->attachTo($repo, '/webmozart/puli/file2');
        $this->assertEquals($clone2, $repo->get('/webmozart/puli/file2'));
    }

    /**
     * @expectedException \Puli\InvalidPathException
     */
    public function testAddExpectsNonEmptyRepositoryPath()
    {
        $this->repo->add('', new TestDirectory());
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
        $this->assertSame($this->repo, $dir->getAttachedRepository());

        $this->assertSame($dir1, $this->repo->get('/webmozart'));
        $this->assertSame('/webmozart', $dir1->getPath());
        $this->assertSame($this->repo, $dir1->getAttachedRepository());

        $this->assertSame($file11, $this->repo->get('/webmozart/file'));
        $this->assertSame('/webmozart/file', $file11->getPath());
        $this->assertSame($this->repo, $file11->getAttachedRepository());
    }

    /**
     * @expectedException \Puli\InvalidPathException
     */
    public function testAddExpectsAbsolutePath()
    {
        $file = new TestFile();

        $this->repo->add('webmozart/puli/', $file);
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
     * @expectedException \Puli\InvalidPathException
     */
    public function testRemoveExpectsNonEmptyPath()
    {
        $this->repo->remove('');
    }

    /**
     * @expectedException \Puli\InvalidPathException
     */
    public function testRemoveExpectsAbsolutePath()
    {
        $this->repo->remove('webmozart/puli');
    }

    public function testTagOne()
    {
        $this->repo->add('/webmozart/puli/file1', $file1 = new TestFile());
        $this->repo->add('/webmozart/puli/file2', new TestFile());

        $this->assertSame(1, $this->repo->tag('/webmozart/puli/file1', 'webmozart/tag'));

        $resources = $this->repo->getByTag('webmozart/tag');

        $this->assertCount(1, $resources);
        $this->assertSame($file1, $resources[0]);
    }

    public function testTagTwice()
    {
        $this->repo->add('/webmozart/puli/file1', new TestFile());

        $this->assertSame(1, $this->repo->tag('/webmozart/puli/file1', 'webmozart/tag'));
        $this->assertSame(0, $this->repo->tag('/webmozart/puli/file1', 'webmozart/tag'));
    }

    public function testTagDot()
    {
        $this->repo->add('/webmozart/puli', $dir1 = new TestDirectory());

        $this->repo->tag('/webmozart/puli/.', 'webmozart/tag');

        $resources = $this->repo->getByTag('webmozart/tag');

        $this->assertCount(1, $resources);
        $this->assertSame($dir1, $resources[0]);
    }

    public function testTagDotDot()
    {
        $this->repo->add('/webmozart/puli', $dir1 = new TestDirectory());

        $this->repo->tag('/webmozart/puli/..', 'webmozart/tag');

        $resources = $this->repo->getByTag('webmozart/tag');

        $this->assertCount(1, $resources);
        $this->assertSame($this->repo->get('/webmozart'), $resources[0]);
    }

    public function testTagMany()
    {
        $this->repo->add('/webmozart/puli/file1', $file1 = new TestFile());
        $this->repo->add('/webmozart/puli/file2', $file2 = new TestFile());

        $this->assertSame(2, $this->repo->tag('/webmozart/puli/file*', 'webmozart/tag'));

        $resources = $this->repo->getByTag('webmozart/tag');

        $this->assertCount(2, $resources);
        $this->assertSame($file1, $resources[0]);
        $this->assertSame($file2, $resources[1]);
    }

    public function testTagDoesNotShowRemovedFiles()
    {
        $this->repo->add('/webmozart/puli/file1', new TestFile());

        $this->repo->tag('/webmozart/puli/file1', 'webmozart/tag');

        $this->repo->remove('/webmozart/puli/file1');

        $this->assertCount(0, $this->repo->getByTag('webmozart/tag'));
    }

    /**
     * @expectedException \Puli\ResourceNotFoundException
     */
    public function testTagExpectsValidPath()
    {
        $this->repo->tag('/foo/bar', 'webmozart/tag');
    }

    /**
     * @expectedException \Puli\InvalidPathException
     */
    public function testTagExpectsAbsolutePath()
    {
        $this->repo->tag('webmozart/puli', 'webmozart/tag');
    }

    public function testUntagOne()
    {
        $this->repo->add('/webmozart/puli/file1', $file1 = new TestFile());
        $this->repo->add('/webmozart/puli/file2', $file2 = new TestFile());

        $this->repo->tag('/webmozart/puli/file1', 'webmozart/tag1');
        $this->repo->tag('/webmozart/puli/file1', 'webmozart/tag2');
        $this->repo->tag('/webmozart/puli/file2', 'webmozart/tag1');

        $this->assertSame(1, $this->repo->untag('/webmozart/puli/file1', 'webmozart/tag1'));

        $resources = $this->repo->getByTag('webmozart/tag1');

        $this->assertCount(1, $resources);
        $this->assertSame($file2, $resources[0]);

        $resources = $this->repo->getByTag('webmozart/tag2');

        $this->assertCount(1, $resources);
        $this->assertSame($file1, $resources[0]);
    }

    public function testUntagTwice()
    {
        $this->repo->add('/webmozart/puli/file1', new TestFile());

        $this->repo->tag('/webmozart/puli/file1', 'webmozart/tag');

        $this->assertSame(1, $this->repo->untag('/webmozart/puli/file1', 'webmozart/tag'));
        $this->assertSame(0, $this->repo->untag('/webmozart/puli/file1', 'webmozart/tag'));
    }

    public function testUntagDot()
    {
        $this->repo->add('/webmozart/puli/file1', $file1 = new TestFile());
        $this->repo->add('/webmozart/puli/file2', $file2 = new TestFile());

        $this->repo->tag('/webmozart/puli', 'webmozart/tag1');
        $this->repo->tag('/webmozart/puli/file1', 'webmozart/tag2');
        $this->repo->tag('/webmozart/puli/file2', 'webmozart/tag1');

        $this->repo->untag('/webmozart/puli/.', 'webmozart/tag1');

        $resources = $this->repo->getByTag('webmozart/tag1');

        $this->assertCount(1, $resources);
        $this->assertSame($file2, $resources[0]);

        $resources = $this->repo->getByTag('webmozart/tag2');

        $this->assertCount(1, $resources);
        $this->assertSame($file1, $resources[0]);
    }

    public function testUntagDotDot()
    {
        $this->repo->add('/webmozart/puli/file1', $file1 = new TestFile());
        $this->repo->add('/webmozart/puli/file2', $file2 = new TestFile());

        $this->repo->tag('/webmozart', 'webmozart/tag1');
        $this->repo->tag('/webmozart/puli/file1', 'webmozart/tag2');
        $this->repo->tag('/webmozart/puli/file2', 'webmozart/tag1');

        $this->repo->untag('/webmozart/puli/..', 'webmozart/tag1');

        $resources = $this->repo->getByTag('webmozart/tag1');

        $this->assertCount(1, $resources);
        $this->assertSame($file2, $resources[0]);

        $resources = $this->repo->getByTag('webmozart/tag2');

        $this->assertCount(1, $resources);
        $this->assertSame($file1, $resources[0]);
    }

    public function testUntagOneIgnoresIfNotTagged()
    {
        $this->repo->add('/webmozart/puli/file1', new TestFile());

        $this->repo->untag('/webmozart/puli/file1', 'webmozart/tag1');
    }

    public function testUntagOneAllTags()
    {
        $this->repo->add('/webmozart/puli/file1', new TestFile());
        $this->repo->add('/webmozart/puli/file2', $file2 = new TestFile());

        $this->repo->tag('/webmozart/puli/file1', 'webmozart/tag1');
        $this->repo->tag('/webmozart/puli/file1', 'webmozart/tag2');
        $this->repo->tag('/webmozart/puli/file2', 'webmozart/tag1');

        $this->assertSame(1, $this->repo->untag('/webmozart/puli/file1'));

        $resources = $this->repo->getByTag('webmozart/tag1');

        $this->assertCount(1, $resources);
        $this->assertSame($file2, $resources[0]);

        $this->assertCount(0, $this->repo->getByTag('webmozart/tag2'));
    }

    public function testUntagMany()
    {
        $this->repo->add('/webmozart/puli/file1', $file1 = new TestFile());
        $this->repo->add('/webmozart/puli/file2', new TestFile());

        $this->repo->tag('/webmozart/puli/file1', 'webmozart/tag1');
        $this->repo->tag('/webmozart/puli/file1', 'webmozart/tag2');
        $this->repo->tag('/webmozart/puli/file2', 'webmozart/tag1');

        $this->assertSame(2, $this->repo->untag('/webmozart/puli/file*', 'webmozart/tag1'));

        $this->assertCount(0, $this->repo->getByTag('webmozart/tag1'));

        $resources = $this->repo->getByTag('webmozart/tag2');

        $this->assertCount(1, $resources);
        $this->assertSame($file1, $resources[0]);
    }

    public function testUntagManyIgnoresIfNotTagged()
    {
        $this->repo->add('/webmozart/puli/file1', new TestFile());
        $this->repo->add('/webmozart/puli/file2', new TestFile());

        $this->repo->untag('/webmozart/puli/file*', 'webmozart/tag1');
    }

    public function testUntagManyAllTags()
    {
        $this->repo->add('/webmozart/puli/file1', $file1 = new TestFile());
        $this->repo->add('/webmozart/puli/file2', $file2 = new TestFile());

        $this->repo->tag('/webmozart/puli/file1', 'webmozart/tag1');
        $this->repo->tag('/webmozart/puli/file1', 'webmozart/tag2');
        $this->repo->tag('/webmozart/puli/file2', 'webmozart/tag1');

        $this->repo->untag('/webmozart/puli/file*');

        $this->assertCount(0, $this->repo->getByTag('webmozart/tag1'));
        $this->assertCount(0, $this->repo->getByTag('webmozart/tag2'));
    }

    /**
     * @expectedException \Puli\ResourceNotFoundException
     */
    public function testUntagExpectsValidPath()
    {
        $this->repo->untag('/foo/bar', 'webmozart/tag');
    }

    /**
     * @expectedException \Puli\InvalidPathException
     */
    public function testUntagExpectsAbsolutePath()
    {
        $this->repo->untag('webmozart/puli', 'webmozart/tag');
    }
}
