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

use Webmozart\Puli\Filesystem\FilesystemLocator;
use Webmozart\Puli\Locator\ResourceLocatorInterface;
use Webmozart\Puli\Pattern\GlobPattern;
use Webmozart\Puli\Repository\ResourceRepository;
use Webmozart\Puli\Resource\DirectoryResource;
use Webmozart\Puli\Resource\DirectoryResourceInterface;
use Webmozart\Puli\Resource\FileResourceInterface;
use Webmozart\Puli\Resource\ResourceCollection;

/**
 * @since  1.0
 * @author Bernhard Schussek <bschussek@gmail.com>
 */
abstract class AbstractResourceLocatorTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var ResourceRepository
     */
    protected $repo;

    protected $fixturesDir;

    /**
     * @var ResourceLocatorInterface
     */
    private $locator;

    /**
     * @param ResourceRepository $repository
     *
     * @return ResourceLocatorInterface
     */
    abstract protected function createLocator(ResourceRepository $repository);

    /**
     * @param string $path
     *
     * @return FileResourceInterface
     */
    abstract protected function createFile($path);

    /**
     * @param string $path
     *
     * @return DirectoryResourceInterface
     */
    abstract protected function createDir($path);

    abstract protected function assertResourceEquals($expected, $actual);

    protected function setUp()
    {
        $this->fixturesDir = __DIR__.'/../Fixtures';
        $this->repo = new ResourceRepository(new FilesystemLocator($this->fixturesDir));
        $this->locator = null;
    }

    public function testContainsPath()
    {
        $this->locator = $this->createLocator($this->repo);

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

        $this->repo->add('/webmozart/puli/file1', $this->createFile('/dir1/file1'));
        $this->repo->add('/webmozart/puli/file2', $this->createFile('/dir1/file2'));

        $this->locator = $this->createLocator($this->repo);

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

    public function testContainsPattern()
    {
        $this->locator = $this->createLocator($this->repo);

        $this->assertFalse($this->locator->contains('/webmozart/*'));
        $this->assertFalse($this->locator->contains('/webmozart/file*'));
        $this->assertFalse($this->locator->contains('/webmozart/puli/file*'));
        $this->assertFalse($this->locator->contains('/webmozart/*/file*'));

        $this->repo->add('/webmozart/puli/file1', $this->createFile('/dir1/file1'));
        $this->repo->add('/webmozart/puli/file2', $this->createFile('/dir1/file2'));

        $this->locator = $this->createLocator($this->repo);

        $this->assertTrue($this->locator->contains('/webmozart/*'));
        $this->assertFalse($this->locator->contains('/webmozart/file*'));
        $this->assertTrue($this->locator->contains('/webmozart/puli/file*'));
        $this->assertTrue($this->locator->contains('/webmozart/*/file*'));
    }

    public function testContainsPatternInstance()
    {
        $this->locator = $this->createLocator($this->repo);

        $this->assertFalse($this->locator->contains(new GlobPattern('/webmozart/*')));
        $this->assertFalse($this->locator->contains(new GlobPattern('/webmozart/file*')));
        $this->assertFalse($this->locator->contains(new GlobPattern('/webmozart/puli/file*')));
        $this->assertFalse($this->locator->contains(new GlobPattern('/webmozart/*/file*')));

        $this->repo->add('/webmozart/puli/file1', $this->createFile('/dir1/file1'));
        $this->repo->add('/webmozart/puli/file2', $this->createFile('/dir1/file2'));

        $this->locator = $this->createLocator($this->repo);

        $this->assertTrue($this->locator->contains(new GlobPattern('/webmozart/*')));
        $this->assertFalse($this->locator->contains(new GlobPattern('/webmozart/file*')));
        $this->assertTrue($this->locator->contains(new GlobPattern('/webmozart/puli/file*')));
        $this->assertTrue($this->locator->contains(new GlobPattern('/webmozart/*/file*')));
    }

    public function testContainsDiscardsTrailingSlash()
    {
        $this->repo->add('/webmozart/puli', $this->createDir('/dir1'));

        $this->locator = $this->createLocator($this->repo);

        $this->assertTrue($this->locator->contains('/webmozart/puli/'));
    }

    public function testContainsInterpretsConsecutiveSlashesAsRoot()
    {
        $this->locator = $this->createLocator($this->repo);

        $this->assertTrue($this->locator->contains('///'));
    }

    public function testGetOne()
    {
        $file = $this->createFile('/dir1/file1');

        $this->repo->add('/webmozart/puli/file1', $file);

        $this->locator = $this->createLocator($this->repo);

        $this->assertResourceEquals($file->copyTo('/webmozart/puli/file1'), $this->locator->get('/webmozart/puli/file1'));
    }

    public function provideSelectors()
    {
        return array(
            array('/webmozart/puli/file*'),
            array(new GlobPattern('/webmozart/puli/file*')),
        );
    }


    public function testGetDiscardsTrailingSlash()
    {
        $dir = $this->createDir('/dir1');

        $this->repo->add('/webmozart/puli', $dir);

        $this->locator = $this->createLocator($this->repo);

        $this->assertResourceEquals($dir->copyTo('/webmozart/puli'), $this->locator->get('/webmozart/puli/'));
    }

    public function testGetInterpretsConsecutiveSlashesAsRoot()
    {
        $this->locator = $this->createLocator($this->repo);

        $this->assertSame($this->locator->get('/'), $this->locator->get('///'));
    }

    /**
     * @expectedException \Webmozart\Puli\Locator\ResourceNotFoundException
     */
    public function testGetExpectsValidResource()
    {
        $this->locator = $this->createLocator($this->repo);

        $this->locator->get('/foo/bar');
    }

    public function testGetDotInDirectory()
    {
        $dir = $this->createDir('/dir1');

        $this->repo->add('/webmozart/puli', $dir);

        $this->locator = $this->createLocator($this->repo);

        $this->assertResourceEquals($dir->copyTo('/webmozart/puli'), $this->locator->get('/webmozart/puli/.'));
    }

    public function testGetDotInFile()
    {
        $file = $this->createFile('/dir1/file1');

        $this->repo->add('/webmozart/puli/file1', $file);

        $this->locator = $this->createLocator($this->repo);

        // We support this case even though it leads to an error if done
        // on a regular file system, because recognizing files would be too
        // big a performance impact
        // You should not rely on this however, as this may change anytime
        $this->assertResourceEquals($file->copyTo('/webmozart/puli/file1'), $this->locator->get('/webmozart/puli/file1/.'));
    }

    public function testGetDotInRoot()
    {
        $this->locator = $this->createLocator($this->repo);

        $this->assertSame($this->locator->get('/'), $this->locator->get('/.'));
    }

    public function testGetDotDotInDirectory()
    {
        $this->repo->add('/webmozart/puli', $this->createDir('/dir1'));

        $this->locator = $this->createLocator($this->repo);

        $this->assertSame($this->locator->get('/webmozart'), $this->locator->get('/webmozart/puli/..'));
    }

    public function testGetDotDotInFile()
    {
        $this->repo->add('/webmozart/puli/file1', $this->createFile('/dir1/file1'));

        $this->locator = $this->createLocator($this->repo);

        // We support this case even though it leads to an error if done
        // on a regular file system, because recognizing files would be too
        // big a performance impact
        // You should not rely on this however, as this may change anytime
        $this->assertSame($this->locator->get('/webmozart/puli'), $this->locator->get('/webmozart/puli/file1/..'));
    }

    public function testGetDotDotInRoot()
    {
        $this->locator = $this->createLocator($this->repo);

        $this->assertSame($this->locator->get('/'), $this->locator->get('/..'));
    }

    public function testGetOverriddenFile()
    {
        $file1 = $this->createFile('/dir1/file1');
        $file2 = $this->createFile('/dir1/file2');

        $this->repo->add('/webmozart/puli/file', $file1);
        $this->repo->add('/webmozart/puli/file', $file2);

        $this->locator = $this->createLocator($this->repo);

        $expected = $file2->copyTo('/webmozart/puli/file')->override($file1->copyTo('/webmozart/puli/file'));

        $this->assertResourceEquals($expected, $this->locator->get('/webmozart/puli/file'));
        $this->assertResourceEquals($expected, $this->locator->get('/webmozart/puli')->get('file'));
    }

    public function testGetOverriddenDirectory()
    {
        $dir1 = $this->createDir('/dir1');
        $dir1->add($this->createFile('/dir1/file1'));
        $dir2 = $this->createDir('/dir2');
        $dir2->add($this->createFile('/dir2/file1'));
        $dir2->add($this->createFile('/dir2/file1-link'));

        $this->repo->add('/webmozart/puli', $dir1);
        $this->repo->add('/webmozart/puli', $dir2);

        $this->locator = $this->createLocator($this->repo);

        $expected = $dir2->copyTo('/webmozart/puli')->override($dir1->copyTo('/webmozart/puli'));

        $this->assertResourceEquals($expected, $this->locator->get('/webmozart/puli'));
        $this->assertResourceEquals($expected, $this->locator->get('/webmozart')->get('puli'));
    }
    /**
     * @dataProvider provideSelectors
     */
    public function testFind($selector)
    {
        $this->repo->add('/webmozart/puli/file1', $this->createFile('/dir1/file1'));
        $this->repo->add('/webmozart/puli/file2', $this->createFile('/dir1/file2'));

        $this->locator = $this->createLocator($this->repo);

        $resources = $this->locator->find($selector);

        $this->assertCount(2, $resources);
        $this->assertInstanceOf('Webmozart\Puli\Resource\ResourceCollectionInterface', $resources);
        $this->assertSame($this->locator->get('/webmozart/puli/file1'), $resources[0]);
        $this->assertSame($this->locator->get('/webmozart/puli/file2'), $resources[1]);
    }

    public function testFindEmptyPattern()
    {
        $this->locator = $this->createLocator($this->repo);

        $resources = $this->locator->find('/foo/*');

        $this->assertCount(0, $resources);
        $this->assertInstanceOf('Webmozart\Puli\Resource\ResourceCollectionInterface', $resources);
    }

    public function testListDirectoryDiscardsTrailingSlash()
    {
        $file1 = $this->createFile('/dir1/file1');
        $file2 = $this->createFile('/dir1/file2');

        $this->repo->add('/webmozart/puli/file1', $file1);
        $this->repo->add('/webmozart/puli/file2', $file2);

        $this->locator = $this->createLocator($this->repo);

        $resources = $this->locator->listDirectory('/webmozart/puli/');

        $this->assertCount(2, $resources);
        $this->assertInstanceOf('Webmozart\Puli\Resource\ResourceCollectionInterface', $resources);
        $this->assertSame($this->locator->get('/webmozart/puli/file1'), $resources['file1']);
        $this->assertSame($this->locator->get('/webmozart/puli/file2'), $resources['file2']);
    }

    /**
     * @expectedException \Webmozart\Puli\Locator\ResourceNotFoundException
     */
    public function testListDirectoryExpectsValidPath()
    {
        $this->locator = $this->createLocator($this->repo);

        $this->locator->listDirectory('/foo/bar');
    }

    /**
     * @expectedException \Webmozart\Puli\Resource\NoDirectoryException
     */
    public function testListDirectoryExpectsDirectory()
    {
        $this->repo->add('/webmozart/puli/file1', $this->createFile('/dir1/file1'));

        $this->locator = $this->createLocator($this->repo);

        $this->locator->listDirectory('/webmozart/puli/file1');
    }

    /**
     * @expectedException \Webmozart\Puli\Resource\NoDirectoryException
     */
    public function testListDotDirectoryExpectsDirectory()
    {
        $this->repo->add('/webmozart/puli/file1', $this->createFile('/dir1/file1'));

        $this->locator = $this->createLocator($this->repo);

        $this->locator->listDirectory('/webmozart/puli/file1/.');
    }

    public function testListDotDotDirectoryInFile()
    {
        $file1 = $this->createFile('/dir1/file1');
        $file2 = $this->createFile('/dir1/file2');

        $this->repo->add('/webmozart/puli/file1', $file1);
        $this->repo->add('/webmozart/puli/file2', $file2);

        $this->locator = $this->createLocator($this->repo);

        // We support this case even though it leads to an error if done
        // on a regular file system, because recognizing files would be too
        // big a performance impact
        // You should not rely on this however, as this may change anytime
        $resources = $this->locator->listDirectory('/webmozart/puli/file1/..');

        $this->assertCount(2, $resources);
        $this->assertInstanceOf('Webmozart\Puli\Resource\ResourceCollectionInterface', $resources);
        $this->assertSame($this->locator->get('/webmozart/puli/file1'), $resources['file1']);
        $this->assertSame($this->locator->get('/webmozart/puli/file2'), $resources['file2']);
    }

    public function testListDirectoryDoesNotShowRemovedFiles()
    {
        $this->repo->add('/webmozart/puli/file1', $this->createFile('/dir1/file1'));
        $this->repo->add('/webmozart/puli/file2', $file2 = $this->createFile('/dir1/file2'));

        $this->repo->remove('/webmozart/puli/file1');

        $this->locator = $this->createLocator($this->repo);

        $resources = $this->locator->listDirectory('/webmozart/puli/');

        $this->assertCount(1, $resources);
        $this->assertInstanceOf('Webmozart\Puli\Resource\ResourceCollectionInterface', $resources);
        $this->assertSame($this->locator->get('/webmozart/puli/file2'), $resources['file2']);
    }

    public function testGetByTag()
    {
        $this->repo->add('/webmozart/puli/file1', $file1 = $this->createFile('/dir1/file1'));
        $this->repo->add('/webmozart/puli/file2', $this->createFile('/dir1/file2'));

        $this->repo->tag('/webmozart/puli/file1', 'webmozart/tag');

        $this->locator = $this->createLocator($this->repo);

        $resources = $this->locator->getByTag('webmozart/tag');

        $this->assertCount(1, $resources);
        $this->assertInstanceOf('Webmozart\Puli\Resource\ResourceCollectionInterface', $resources);
        $this->assertSame($this->locator->get('/webmozart/puli/file1'), $resources[0]);
    }

    public function testGetByTagIgnoresNonExistingTags()
    {
        $this->locator = $this->createLocator($this->repo);

        $resources = $this->locator->getByTag('foo/bar');

        $this->assertCount(0, $resources);
        $this->assertInstanceOf('Webmozart\Puli\Resource\ResourceCollectionInterface', $resources);
    }

    public function testGetTags()
    {
        $this->repo->add('/webmozart/puli/file1', $this->createFile('/dir1/file1'));
        $this->repo->add('/webmozart/puli/file2', $this->createFile('/dir1/file2'));

        $this->repo->tag('/webmozart/puli/file1', 'webmozart/tag1');

        $this->locator = $this->createLocator($this->repo);

        $tags = $this->locator->getTags();

        $this->assertCount(1, $tags);
        $this->assertEquals('webmozart/tag1', $tags[0]);

        $this->repo->tag('/webmozart/puli/file2', 'webmozart/tag2');

        $this->locator = $this->createLocator($this->repo);

        $tags = $this->locator->getTags();

        $this->assertCount(2, $tags);
        $this->assertEquals('webmozart/tag1', $tags[0]);
        $this->assertEquals('webmozart/tag2', $tags[1]);

        $this->repo->untag('/webmozart/puli/file1', 'webmozart/tag1');

        $this->locator = $this->createLocator($this->repo);

        $tags = $this->locator->getTags();

        $this->assertCount(1, $tags);
        $this->assertEquals('webmozart/tag2', $tags[0]);
    }

    public function testGetTagsReturnsSortedResult()
    {
        $this->repo->add('/webmozart/puli/file1', $this->createFile('/dir1/file1'));

        $this->repo->tag('/webmozart/puli/file1', 'webmozart/foo');
        $this->repo->tag('/webmozart/puli/file1', 'webmozart/bar');

        $this->locator = $this->createLocator($this->repo);

        $tags = $this->locator->getTags();

        $this->assertCount(2, $tags);
        $this->assertEquals('webmozart/bar', $tags[0]);
        $this->assertEquals('webmozart/foo', $tags[1]);
    }
}
