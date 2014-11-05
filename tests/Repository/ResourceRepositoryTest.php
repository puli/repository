<?php

/*
 * This file is part of the Puli package.
 *
 * (c) Bernhard Schussek <bschussek@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Webmozart\Puli\Tests\Repository;

use Webmozart\Puli\Pattern\GlobPattern;
use Webmozart\Puli\Repository\ResourceRepository;
use Webmozart\Puli\Resource\ResourceCollection;
use Webmozart\Puli\Tests\Locator\AbstractResourceLocatorTest;
use Webmozart\Puli\Tests\Resource\TestDirectory;
use Webmozart\Puli\Tests\Resource\TestFile;

/**
 * @since  1.0
 * @author Bernhard Schussek <bschussek@gmail.com>
 */
class ResourceRepositoryTest extends AbstractResourceLocatorTest
{
    public function testAddFile()
    {
        $file = $this->createFile('/dir1/file1');

        $this->repo->add('/webmozart/puli/file1', $file);

        $this->assertEquals($file->copyTo('/webmozart/puli/file1'), $this->repo->get('/webmozart/puli/file1'));
    }

    public function testAddDirectory()
    {
        $dir = $this->createDir('/dir1');
        $dir->add($file1 = $this->createFile('/dir1/file1'));
        $dir->add($file2 = $this->createFile('/dir1/file2'));

        $this->repo->add('/webmozart/puli', $dir);

        $this->assertEquals($dir->copyTo('/webmozart/puli'), $this->repo->get('/webmozart/puli'));
        $this->assertEquals($file1->copyTo('/webmozart/puli/file1'), $this->repo->get('/webmozart/puli/file1'));
        $this->assertEquals($file2->copyTo('/webmozart/puli/file2'), $this->repo->get('/webmozart/puli/file2'));
        $this->assertCount(2, $this->repo->get('/webmozart/puli')->listEntries());
    }

    public function testOverrideFile()
    {
        $file1 = $this->createFile('/dir1/file1');
        $file2 = $this->getMock('Webmozart\Puli\Resource\FileResourceInterface');
        $merged = $this->createFile('/webmozart/puli/file1');

        $file2->expects($this->once())
            ->method('override')
            ->with($file1->copyTo('/webmozart/puli/file1'))
            ->will($this->returnValue($merged));

        $this->repo->add('/webmozart/puli/file1', $file1);
        $this->repo->add('/webmozart/puli/file1', $file2);

        $this->assertSame($merged, $this->repo->get('/webmozart/puli/file1'));
        $this->assertSame($merged, $this->repo->get('/webmozart/puli')->get('file1'));
    }

    public function testOverrideDirectory()
    {
        $dir1 = $this->createDir('/dir1');
        $dir2 = $this->getMock('Webmozart\Puli\Resource\DirectoryResourceInterface');
        $merged = $this->createDir('/webmozart/puli');
        $merged->add($this->createFile('/webmozart/puli/file1'));
        $merged->add($this->createFile('/webmozart/puli/file2'));

        $dir2->expects($this->once())
            ->method('override')
            ->with($dir1->copyTo('/webmozart/puli'))
            ->will($this->returnValue($merged));

        $this->repo->add('/webmozart/puli', $dir1);
        $this->repo->add('/webmozart/puli', $dir2);

        $this->assertSame($merged, $this->repo->get('/webmozart/puli'));
        $this->assertSame($merged, $this->repo->get('/webmozart')->get('puli'));
    }

    /**
     * @expectedException \Webmozart\Puli\Resource\NoDirectoryException
     */
    public function testAddFileAsChildOfFile()
    {
        $file1 = $this->createFile('/dir');
        $file2 = $this->createFile('/file');

        $this->repo->add('/webmozart/puli', $file1);
        $this->repo->add('/webmozart/puli/file', $file2);
    }

    public function testAddFileAsChildOfDirectory()
    {
        $dir = $this->createDir('/dir');
        $file = $this->createFile('/file');

        $this->repo->add('/webmozart/puli', $dir);
        $this->repo->add('/webmozart/puli/file', $file);

        $this->assertEquals($file->copyTo('/webmozart/puli/file'), $this->repo->get('/webmozart/puli/file'));
    }

    public function testAddDot()
    {
        $file = $this->createFile('/dir1/file1');

        $this->repo->add('/webmozart/puli/file1/.', $file);

        $this->assertEquals($file->copyTo('/webmozart/puli/file1'), $this->repo->get('/webmozart/puli/file1'));
    }

    public function testAddDotDot()
    {
        $file = $this->createFile('/dir1/file1');

        $this->repo->add('/webmozart/puli/file1/..', $file);

        $this->assertEquals($file->copyTo('/webmozart/puli'), $this->repo->get('/webmozart/puli'));
    }

    public function testAddTrimsTrailingSlash()
    {
        $file = $this->createFile('/dir1/file1');

        $this->repo->add('/webmozart/puli/', $file);

        $this->assertEquals($file->copyTo('/webmozart/puli'), $this->repo->get('/webmozart/puli'));
    }

    public function testAddCollection()
    {
        $file1 = $this->createFile('/dir1/file2');
        $file2 = $this->createFile('/dir2/file1');

        $this->repo->add('/webmozart/puli', new ResourceCollection(array($file1, $file2)));

        $this->assertEquals($file2->copyTo('/webmozart/puli/file1'), $this->repo->get('/webmozart/puli/file1'));
        $this->assertEquals($file1->copyTo('/webmozart/puli/file2'), $this->repo->get('/webmozart/puli/file2'));
    }

    public function testAddFromBackend()
    {
        $backend = $this->getMock('Webmozart\Puli\Locator\ResourceLocatorInterface');
        $file = $this->createFile('/dir1/file1');

        $backend->expects($this->once())
            ->method('get')
            ->with('/dir1/file1')
            ->will($this->returnValue($file));

        $repo = new ResourceRepository($backend);
        $repo->add('/webmozart/puli/file1', '/dir1/file1');

        $this->assertEquals($file->copyTo('/webmozart/puli/file1'), $repo->get('/webmozart/puli/file1'));
    }

    /**
     * @expectedException \InvalidArgumentException
     */
    public function testAddExpectsNonEmptyRepositoryPath()
    {
        $this->repo->add('', '/dir1');
    }

    /**
     * @expectedException \InvalidArgumentException
     */
    public function testAddExpectsNonRootRepositoryPath()
    {
        $this->repo->add('/', '/dir1');
    }

    public function testRemoveFile()
    {
        $this->repo->add('/webmozart/puli/file1', $this->createFile('/file1'));
        $this->repo->add('/webmozart/puli/file2', $this->createFile('/file2'));

        $this->assertTrue($this->repo->contains('/webmozart'));
        $this->assertTrue($this->repo->contains('/webmozart/puli'));
        $this->assertTrue($this->repo->contains('/webmozart/puli/file1'));
        $this->assertTrue($this->repo->contains('/webmozart/puli/file2'));

        $this->repo->remove('/webmozart/puli/file1');

        $this->assertTrue($this->repo->contains('/webmozart'));
        $this->assertTrue($this->repo->contains('/webmozart/puli'));
        $this->assertFalse($this->repo->contains('/webmozart/puli/file1'));
        $this->assertTrue($this->repo->contains('/webmozart/puli/file2'));
    }

    /**
     * @dataProvider provideSelectors
     */
    public function testRemoveMany($selector)
    {
        $this->repo->add('/webmozart/puli/file1', $this->createFile('/file1'));
        $this->repo->add('/webmozart/puli/file2', $this->createFile('/file2'));

        $this->assertTrue($this->repo->contains('/webmozart/puli'));
        $this->assertTrue($this->repo->contains('/webmozart/puli/file1'));
        $this->assertTrue($this->repo->contains('/webmozart/puli/file2'));

        $this->repo->remove($selector);

        $this->assertTrue($this->repo->contains('/webmozart/puli'));
        $this->assertFalse($this->repo->contains('/webmozart/puli/file1'));
        $this->assertFalse($this->repo->contains('/webmozart/puli/file2'));
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
     * @dataProvider provideDirectorySelector
     */
    public function testRemoveDirectory($selector)
    {
        $this->repo->add('/webmozart/puli/file1', $this->createFile('/file1'));
        $this->repo->add('/webmozart/puli/file2', $this->createFile('/file2'));

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
        $this->repo->add('/webmozart/puli/file1', $this->createFile('/file1'));
        $this->repo->add('/webmozart/puli/file2', $this->createFile('/file2'));

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
        $this->repo->add('/webmozart/puli/file1', $this->createFile('/file1'));
        $this->repo->add('/webmozart/puli/file2', $this->createFile('/file2'));

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
        $this->repo->add('/webmozart/puli/file1', $this->createFile('/file1'));
        $this->repo->add('/webmozart/puli/file2', $this->createFile('/file2'));

        $this->assertTrue($this->repo->contains('/webmozart/puli'));
        $this->assertTrue($this->repo->contains('/webmozart/puli/file1'));
        $this->assertTrue($this->repo->contains('/webmozart/puli/file2'));

        $this->repo->remove('/webmozart/puli/');

        $this->assertFalse($this->repo->contains('/webmozart/puli'));
        $this->assertFalse($this->repo->contains('/webmozart/puli/file1'));
        $this->assertFalse($this->repo->contains('/webmozart/puli/file2'));
    }

    /**
     * @expectedException \Webmozart\Puli\Repository\UnsupportedOperationException
     */
    public function testRemoveDoesNotRemoveRoot()
    {
        $this->repo->remove('/');
    }

    /**
     * @expectedException \InvalidArgumentException
     */
    public function testRemoveExpectsNonEmptyPath()
    {
        $this->repo->remove('');
    }

    /**
     * @expectedException \Webmozart\Puli\Repository\UnsupportedOperationException
     */
    public function testRemoveInterpretsConsecutiveSlashesAsRoot()
    {
        $this->repo->remove('///');
    }

    public function testTagOne()
    {
        $this->repo->add('/webmozart/puli/file1', $file1 = $this->createFile('/file1'));
        $this->repo->add('/webmozart/puli/file2', $this->createFile('/file2'));

        $this->repo->tag('/webmozart/puli/file1', 'webmozart/tag');

        $resources = $this->repo->getByTag('webmozart/tag');

        $this->assertCount(1, $resources);
        $this->assertEquals($file1->copyTo('/webmozart/puli/file1'), $resources[0]);
    }

    public function testTagDot()
    {
        $this->repo->add('/webmozart/puli', $dir1 = $this->createDir('/dir1'));

        $this->repo->tag('/webmozart/puli/.', 'webmozart/tag');

        $resources = $this->repo->getByTag('webmozart/tag');

        $this->assertCount(1, $resources);
        $this->assertEquals($dir1->copyTo('/webmozart/puli'), $resources[0]);
    }

    public function testTagDotDot()
    {
        $this->repo->add('/webmozart/puli', $dir1 = $this->createDir('/dir1'));

        $this->repo->tag('/webmozart/puli/..', 'webmozart/tag');

        $resources = $this->repo->getByTag('webmozart/tag');

        $this->assertCount(1, $resources);
        $this->assertEquals($this->repo->get('/webmozart'), $resources[0]);
    }

    /**
     * @dataProvider provideSelectors
     */
    public function testTagMany($selector)
    {
        $this->repo->add('/webmozart/puli/file1', $file1 = $this->createFile('/file1'));
        $this->repo->add('/webmozart/puli/file2', $file2 = $this->createFile('/file2'));

        $this->repo->tag($selector, 'webmozart/tag');

        $resources = $this->repo->getByTag('webmozart/tag');

        $this->assertCount(2, $resources);
        $this->assertEquals($file1->copyTo('/webmozart/puli/file1'), $resources[0]);
        $this->assertEquals($file2->copyTo('/webmozart/puli/file2'), $resources[1]);
    }

    public function testTagDoesNotShowRemovedFiles()
    {
        $this->repo->add('/webmozart/puli/file1', $this->createFile('/file1'));

        $this->repo->tag('/webmozart/puli/file1', 'webmozart/tag');

        $this->repo->remove('/webmozart/puli/file1');

        $this->assertCount(0, $this->repo->getByTag('webmozart/tag'));
    }

    /**
     * @expectedException \Webmozart\Puli\Locator\ResourceNotFoundException
     */
    public function testTagExpectsValidPath()
    {
        $this->repo->tag('/foo/bar', 'webmozart/tag');
    }

    public function testUntagOne()
    {
        $this->repo->add('/webmozart/puli/file1', $file1 = $this->createFile('/file1'));
        $this->repo->add('/webmozart/puli/file2', $file2 = $this->createFile('/file2'));

        $this->repo->tag('/webmozart/puli/file1', 'webmozart/tag1');
        $this->repo->tag('/webmozart/puli/file1', 'webmozart/tag2');
        $this->repo->tag('/webmozart/puli/file2', 'webmozart/tag1');

        $this->repo->untag('/webmozart/puli/file1', 'webmozart/tag1');

        $resources = $this->repo->getByTag('webmozart/tag1');

        $this->assertCount(1, $resources);
        $this->assertEquals($file2->copyTo('/webmozart/puli/file2'), $resources[0]);

        $resources = $this->repo->getByTag('webmozart/tag2');

        $this->assertCount(1, $resources);
        $this->assertEquals($file1->copyTo('/webmozart/puli/file1'), $resources[0]);
    }

    public function testUntagDot()
    {
        $this->repo->add('/webmozart/puli/file1', $file1 = $this->createFile('/file1'));
        $this->repo->add('/webmozart/puli/file2', $file2 = $this->createFile('/file2'));

        $this->repo->tag('/webmozart/puli', 'webmozart/tag1');
        $this->repo->tag('/webmozart/puli/file1', 'webmozart/tag2');
        $this->repo->tag('/webmozart/puli/file2', 'webmozart/tag1');

        $this->repo->untag('/webmozart/puli/.', 'webmozart/tag1');

        $resources = $this->repo->getByTag('webmozart/tag1');

        $this->assertCount(1, $resources);
        $this->assertEquals($file2->copyTo('/webmozart/puli/file2'), $resources[0]);

        $resources = $this->repo->getByTag('webmozart/tag2');

        $this->assertCount(1, $resources);
        $this->assertEquals($file1->copyTo('/webmozart/puli/file1'), $resources[0]);
    }

    public function testUntagDotDot()
    {
        $this->repo->add('/webmozart/puli/file1', $file1 = $this->createFile('/file1'));
        $this->repo->add('/webmozart/puli/file2', $file2 = $this->createFile('/file2'));

        $this->repo->tag('/webmozart', 'webmozart/tag1');
        $this->repo->tag('/webmozart/puli/file1', 'webmozart/tag2');
        $this->repo->tag('/webmozart/puli/file2', 'webmozart/tag1');

        $this->repo->untag('/webmozart/puli/..', 'webmozart/tag1');

        $resources = $this->repo->getByTag('webmozart/tag1');

        $this->assertCount(1, $resources);
        $this->assertEquals($file2->copyTo('/webmozart/puli/file2'), $resources[0]);

        $resources = $this->repo->getByTag('webmozart/tag2');

        $this->assertCount(1, $resources);
        $this->assertEquals($file1->copyTo('/webmozart/puli/file1'), $resources[0]);
    }

    public function testUntagOneIgnoresIfNotTagged()
    {
        $this->repo->add('/webmozart/puli/file1', $this->createFile('/file1'));

        $this->repo->untag('/webmozart/puli/file1', 'webmozart/tag1');
    }

    public function testUntagOneAllTags()
    {
        $this->repo->add('/webmozart/puli/file1', $this->createFile('/file1'));
        $this->repo->add('/webmozart/puli/file2', $file2 = $this->createFile('/file2'));

        $this->repo->tag('/webmozart/puli/file1', 'webmozart/tag1');
        $this->repo->tag('/webmozart/puli/file1', 'webmozart/tag2');
        $this->repo->tag('/webmozart/puli/file2', 'webmozart/tag1');

        $this->repo->untag('/webmozart/puli/file1');

        $resources = $this->repo->getByTag('webmozart/tag1');

        $this->assertCount(1, $resources);
        $this->assertEquals($file2->copyTo('/webmozart/puli/file2'), $resources[0]);

        $this->assertCount(0, $this->repo->getByTag('webmozart/tag2'));
    }

    /**
     * @dataProvider provideSelectors
     */
    public function testUntagMany($selector)
    {
        $this->repo->add('/webmozart/puli/file1', $file1 = $this->createFile('/file1'));
        $this->repo->add('/webmozart/puli/file2', $this->createFile('/file2'));

        $this->repo->tag('/webmozart/puli/file1', 'webmozart/tag1');
        $this->repo->tag('/webmozart/puli/file1', 'webmozart/tag2');
        $this->repo->tag('/webmozart/puli/file2', 'webmozart/tag1');

        $this->repo->untag($selector, 'webmozart/tag1');

        $this->assertCount(0, $this->repo->getByTag('webmozart/tag1'));

        $resources = $this->repo->getByTag('webmozart/tag2');

        $this->assertCount(1, $resources);
        $this->assertEquals($file1->copyTo('/webmozart/puli/file1'), $resources[0]);
    }

    /**
     * @dataProvider provideSelectors
     */
    public function testUntagManyIgnoresIfNotTagged($selector)
    {
        $this->repo->add('/webmozart/puli/file1', $this->createFile('/file1'));
        $this->repo->add('/webmozart/puli/file2', $this->createFile('/file2'));

        $this->repo->untag($selector, 'webmozart/tag1');
    }

    /**
     * @dataProvider provideSelectors
     */
    public function testUntagManyAllTags($selector)
    {
        $this->repo->add('/webmozart/puli/file1', $file1 = $this->createFile('/file1'));
        $this->repo->add('/webmozart/puli/file2', $file2 = $this->createFile('/file2'));

        $this->repo->tag('/webmozart/puli/file1', 'webmozart/tag1');
        $this->repo->tag('/webmozart/puli/file1', 'webmozart/tag2');
        $this->repo->tag('/webmozart/puli/file2', 'webmozart/tag1');

        $this->repo->untag($selector);

        $this->assertCount(0, $this->repo->getByTag('webmozart/tag1'));
        $this->assertCount(0, $this->repo->getByTag('webmozart/tag2'));
    }

    /**
     * @expectedException \Webmozart\Puli\Locator\ResourceNotFoundException
     */
    public function testUntagExpectsValidPath()
    {
        $this->repo->untag('/foo/bar', 'webmozart/tag');
    }

    protected function createLocator(ResourceRepository $repo)
    {
        return $repo;
    }

    protected function createFile($path)
    {
        return new TestFile($path);
    }

    protected function createDir($path)
    {
        return new TestDirectory($path);
    }

    protected function assertResourceEquals($expected, $actual)
    {
        $this->assertEquals($expected, $actual);
    }
}
