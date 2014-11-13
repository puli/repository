<?php

/*
 * This file is part of the Puli package.
 *
 * (c) Bernhard Schussek <bschussek@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Puli\Tests\Repository;

use Puli\Resource\DirectoryResourceInterface;
use Puli\Repository\ResourceRepositoryInterface;
use Puli\Tests\Resource\TestDirectory;
use Puli\Tests\Resource\TestFile;

/**
 * @since  1.0
 * @author Bernhard Schussek <bschussek@gmail.com>
 */
abstract class AbstractRepositoryTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @param DirectoryResourceInterface $root
     *
     * @return ResourceRepositoryInterface
     */
    abstract protected function createRepository(DirectoryResourceInterface $root, array $tags = array());

    abstract protected function assertSameResource($expected, $actual);

    protected function pass()
    {
        $this->assertTrue(true);
    }

    public function testContainsPath()
    {
        $repo = $this->createRepository(new TestDirectory('/'));

        $this->assertTrue($repo->contains('/'));
        $this->assertTrue($repo->contains('/.'));
        $this->assertTrue($repo->contains('/..'));
        $this->assertFalse($repo->contains('/webmozart'));
        $this->assertFalse($repo->contains('/webmozart/.'));
        $this->assertTrue($repo->contains('/webmozart/..'));
        $this->assertFalse($repo->contains('/./webmozart'));
        $this->assertFalse($repo->contains('/../webmozart'));
        $this->assertFalse($repo->contains('/webmozart/../webmozart'));
        $this->assertFalse($repo->contains('/webmozart/puli'));
        $this->assertFalse($repo->contains('/webmozart/puli/.'));
        $this->assertFalse($repo->contains('/webmozart/puli/..'));
        $this->assertFalse($repo->contains('/webmozart/./puli'));
        $this->assertFalse($repo->contains('/webmozart/././puli'));
        $this->assertFalse($repo->contains('/webmozart/../webmozart/puli'));
        $this->assertFalse($repo->contains('/webmozart/../../webmozart/puli'));
        $this->assertFalse($repo->contains('/webmozart/../puli'));
        $this->assertFalse($repo->contains('/webmozart/./../puli'));
        $this->assertFalse($repo->contains('/webmozart/.././puli'));
        $this->assertFalse($repo->contains('/webmozart/puli/file1'));
        $this->assertFalse($repo->contains('/webmozart/puli/file1/.'));
        $this->assertFalse($repo->contains('/webmozart/puli/file1/..'));
        $this->assertFalse($repo->contains('/webmozart/puli/file2'));
        $this->assertFalse($repo->contains('/webmozart/puli/file2/.'));
        $this->assertFalse($repo->contains('/webmozart/puli/file2/..'));

        $repo = $this->createRepository(new TestDirectory('/', array(
            new TestDirectory('/webmozart', array(
                new TestDirectory('/webmozart/puli', array(
                    new TestFile('/webmozart/puli/file1'),
                    new TestFile('/webmozart/puli/file2'),
                )),
            )),
        )));

        $this->assertTrue($repo->contains('/'));
        $this->assertTrue($repo->contains('/.'));
        $this->assertTrue($repo->contains('/..'));
        $this->assertTrue($repo->contains('/webmozart'));
        $this->assertTrue($repo->contains('/webmozart/.'));
        $this->assertTrue($repo->contains('/webmozart/..'));
        $this->assertTrue($repo->contains('/./webmozart'));
        $this->assertTrue($repo->contains('/../webmozart'));
        $this->assertTrue($repo->contains('/webmozart/puli'));
        $this->assertTrue($repo->contains('/webmozart/puli/.'));
        $this->assertTrue($repo->contains('/webmozart/puli/..'));
        $this->assertTrue($repo->contains('/webmozart/./puli'));
        $this->assertTrue($repo->contains('/webmozart/././puli'));
        $this->assertTrue($repo->contains('/webmozart/../webmozart/puli'));
        $this->assertTrue($repo->contains('/webmozart/../../webmozart/puli'));
        $this->assertFalse($repo->contains('/webmozart/../puli'));
        $this->assertFalse($repo->contains('/webmozart/./../puli'));
        $this->assertFalse($repo->contains('/webmozart/.././puli'));
        $this->assertTrue($repo->contains('/webmozart/puli/file1'));
        $this->assertTrue($repo->contains('/webmozart/puli/file1/.'));
        $this->assertTrue($repo->contains('/webmozart/puli/file1/..'));
        $this->assertTrue($repo->contains('/webmozart/puli/file2'));
        $this->assertTrue($repo->contains('/webmozart/puli/file2/.'));
        $this->assertTrue($repo->contains('/webmozart/puli/file2/..'));
    }

    public function testContainsPattern()
    {
        $repo = $this->createRepository(new TestDirectory('/'));

        $this->assertFalse($repo->contains('/webmozart/*'));
        $this->assertFalse($repo->contains('/webmozart/file*'));
        $this->assertFalse($repo->contains('/webmozart/puli/file*'));
        $this->assertFalse($repo->contains('/webmozart/*/file*'));

        $repo = $this->createRepository(new TestDirectory('/', array(
            new TestDirectory('/webmozart', array(
                new TestDirectory('/webmozart/puli', array(
                    new TestFile('/webmozart/puli/file1'),
                    new TestFile('/webmozart/puli/file2'),
                )),
            )),
        )));

        $this->assertTrue($repo->contains('/webmozart/*'));
        $this->assertFalse($repo->contains('/webmozart/file*'));
        $this->assertTrue($repo->contains('/webmozart/puli/file*'));
        $this->assertTrue($repo->contains('/webmozart/*/file*'));
    }

    public function testContainsDiscardsTrailingSlash()
    {
        $repo = $this->createRepository(new TestDirectory('/', array(
            new TestDirectory('/webmozart'),
        )));

        $this->assertTrue($repo->contains('/webmozart/'));
    }

    public function testContainsInterpretsConsecutiveSlashesAsRoot()
    {
        $repo = $this->createRepository(new TestDirectory('/'));

        $this->assertTrue($repo->contains('///'));
    }

    /**
     * @expectedException \Puli\Repository\InvalidPathException
     */
    public function testContainsExpectsAbsolutePath()
    {
        $repo = $this->createRepository(new TestDirectory('/', array(
            new TestDirectory('/webmozart'),
        )));

        $repo->contains('webmozart');
    }

    abstract public function testGetFile();

    abstract public function testGetDirectory();

    abstract public function testGetOverriddenFile();

    abstract public function testGetOverriddenDirectory();

    public function testGetDiscardsTrailingSlash()
    {
        $repo = $this->createRepository(new TestDirectory('/', array(
            new TestDirectory('/webmozart'),
        )));

        $this->assertSameResource($repo->get('/webmozart'), $repo->get('/webmozart/'));
    }

    public function testGetInterpretsConsecutiveSlashesAsRoot()
    {
        $repo = $this->createRepository(new TestDirectory('/'));

        $this->assertSameResource($repo->get('/'), $repo->get('///'));
    }

    public function testGetCanonicalizesFilePaths()
    {
        $repo = $this->createRepository(new TestDirectory('/', array(
            new TestDirectory('/webmozart', array(
                new TestDirectory('/webmozart/puli', array(
                    new TestFile('/webmozart/puli/file'),
                )),
            )),
        )));

        $this->assertSameResource($repo->get('/webmozart/puli/file'), $repo->get('/webmozart/puli/../puli/./file'));
    }

    public function testGetCanonicalizesDirectoryPaths()
    {
        $repo = $this->createRepository(new TestDirectory('/', array(
            new TestDirectory('/webmozart', array(
                new TestDirectory('/webmozart/puli', array(
                    new TestDirectory('/webmozart/puli/dir'),
                )),
            )),
        )));

        $this->assertSameResource($repo->get('/webmozart/puli/dir'), $repo->get('/webmozart/puli/../puli/dir'));
    }

    /**
     * @expectedException \Puli\Repository\ResourceNotFoundException
     */
    public function testGetExpectsExistingResource()
    {
        $repo = $this->createRepository(new TestDirectory('/'));

        $repo->get('/foo/bar');
    }

    /**
     * @expectedException \Puli\Repository\InvalidPathException
     */
    public function testGetExpectsAbsolutePath()
    {
        $repo = $this->createRepository(new TestDirectory('/', array(
            new TestDirectory('/webmozart'),
        )));

        $repo->get('webmozart');
    }

    public function testGetDotInDirectory()
    {
        $repo = $this->createRepository(new TestDirectory('/', array(
            new TestDirectory('/webmozart'),
        )));

        $this->assertSameResource($repo->get('/webmozart'), $repo->get('/webmozart/.'));
    }

    public function testGetDotInFile()
    {
        $repo = $this->createRepository(new TestDirectory('/', array(
            new TestDirectory('/webmozart', array(
                new TestDirectory('/webmozart/puli', array(
                    new TestFile('/webmozart/puli/file1'),
                )),
            )),
        )));

        // We support this case even though it leads to an error if done
        // on a regular file system, because recognizing files would be too
        // big a performance impact
        // You should not rely on this however, as this may change anytime
        $this->assertSameResource($repo->get('/webmozart/puli/file1'), $repo->get('/webmozart/puli/file1/.'));
    }

    public function testGetDotInRoot()
    {
        $repo = $this->createRepository(new TestDirectory('/'));

        $this->assertSameResource($repo->get('/'), $repo->get('/.'));
    }

    public function testGetDotDotInDirectory()
    {
        $repo = $this->createRepository(new TestDirectory('/', array(
            new TestDirectory('/webmozart', array(
                new TestDirectory('/webmozart/puli'),
            )),
        )));

        $this->assertSameResource($repo->get('/webmozart'), $repo->get('/webmozart/puli/..'));
    }

    public function testGetDotDotInFile()
    {
        $repo = $this->createRepository(new TestDirectory('/', array(
            new TestDirectory('/webmozart', array(
                new TestDirectory('/webmozart/puli', array(
                    new TestFile('/webmozart/puli/file'),
                )),
            )),
        )));

        // We support this case even though it leads to an error if done
        // on a regular file system, because recognizing files would be too
        // big a performance impact
        // You should not rely on this however, as this may change anytime
        $this->assertSameResource($repo->get('/webmozart/puli'), $repo->get('/webmozart/puli/file1/..'));
    }

    public function testGetDotDotInRoot()
    {
        $repo = $this->createRepository(new TestDirectory('/'));

        $this->assertSameResource($repo->get('/'), $repo->get('/..'));
    }

    public function testFind()
    {
        $repo = $this->createRepository(new TestDirectory('/', array(
            new TestDirectory('/webmozart', array(
                new TestDirectory('/webmozart/puli', array(
                    new TestFile('/webmozart/puli/.dotfile'),
                    new TestFile('/webmozart/puli/foo'),
                    new TestFile('/webmozart/puli/bar'),
                    new TestDirectory('/webmozart/puli/dir'),
                )),
            )),
        )));

        $resources = $repo->find('/webmozart/puli/*');

        $this->assertCount(4, $resources);
        $this->assertInstanceOf('Puli\Resource\Collection\ResourceCollectionInterface', $resources);
        // sorted
        $this->assertSameResource($repo->get('/webmozart/puli/.dotfile'), $resources[0]);
        $this->assertSameResource($repo->get('/webmozart/puli/bar'), $resources[1]);
        $this->assertSameResource($repo->get('/webmozart/puli/dir'), $resources[2]);
        $this->assertSameResource($repo->get('/webmozart/puli/foo'), $resources[3]);
    }

    public function testFindFile()
    {
        $repo = $this->createRepository(new TestDirectory('/', array(
            new TestDirectory('/webmozart', array(
                new TestDirectory('/webmozart/puli', array(
                    new TestFile('/webmozart/puli/file'),
                )),
            )),
        )));

        $resources = $repo->find('/webmozart/puli/file');

        $this->assertCount(1, $resources);
        $this->assertInstanceOf('Puli\Resource\Collection\ResourceCollectionInterface', $resources);
        $this->assertSameResource($repo->get('/webmozart/puli/file'), $resources[0]);
    }

    public function testFindDirectory()
    {
        $repo = $this->createRepository(new TestDirectory('/', array(
            new TestDirectory('/webmozart'),
        )));

        $resources = $repo->find('/webmozart');

        $this->assertCount(1, $resources);
        $this->assertInstanceOf('Puli\Resource\Collection\ResourceCollectionInterface', $resources);
        $this->assertSameResource($repo->get('/webmozart'), $resources[0]);
    }

    public function testFindCanonicalizesSelector()
    {
        $repo = $this->createRepository(new TestDirectory('/', array(
            new TestDirectory('/webmozart', array(
                new TestDirectory('/webmozart/puli', array(
                    new TestFile('/webmozart/puli/file1'),
                )),
            )),
        )));

        $resources = $repo->find('/webmozart/puli/../puli/./*');

        $this->assertCount(1, $resources);
        $this->assertInstanceOf('Puli\Resource\Collection\ResourceCollectionInterface', $resources);
        $this->assertSameResource($repo->get('/webmozart/puli/file1'), $resources[0]);
    }

    public function testFindNoMatches()
    {
        $repo = $this->createRepository(new TestDirectory('/'));

        $resources = $repo->find('/foo/*');

        $this->assertCount(0, $resources);
        $this->assertInstanceOf('Puli\Resource\Collection\ResourceCollectionInterface', $resources);
    }

    /**
     * @expectedException \Puli\Repository\InvalidPathException
     */
    public function testFindExpectsAbsolutePath()
    {
        $repo = $this->createRepository(new TestDirectory('/'));

        $repo->find('*');
    }

    public function testGetByTag()
    {
        $repo = $this->createRepository(
            new TestDirectory('/', array(
                new TestDirectory('/webmozart', array(
                    new TestDirectory('/webmozart/puli', array(
                        new TestFile('/webmozart/puli/file1'),
                        new TestFile('/webmozart/puli/file2'),
                    )),
                )),
            )),
            array(
                '/webmozart/puli/file1' => 'webmozart/tag',
            )
        );

        $resources = $repo->getByTag('webmozart/tag');

        $this->assertCount(1, $resources);
        $this->assertInstanceOf('Puli\Resource\Collection\ResourceCollectionInterface', $resources);
        $this->assertSameResource($repo->get('/webmozart/puli/file1'), $resources[0]);
    }

    public function testGetByTagIgnoresNonExistingTags()
    {
        $repo = $this->createRepository(new TestDirectory('/'));

        $resources = $repo->getByTag('foo/bar');

        $this->assertCount(0, $resources);
        $this->assertInstanceOf('Puli\Resource\Collection\ResourceCollectionInterface', $resources);
    }

    public function testGetTags()
    {
        $repo = $this->createRepository(
            new TestDirectory('/', array(
                new TestDirectory('/webmozart', array(
                    new TestDirectory('/webmozart/puli', array(
                        new TestFile('/webmozart/puli/file1'),
                        new TestFile('/webmozart/puli/file2'),
                    )),
                )),
            )),
            array(
                '/webmozart/puli/file1' => 'webmozart/tag1',
                '/webmozart/puli/file2' => 'webmozart/tag2',
            )
        );

        $tags = $repo->getTags();

        $this->assertCount(2, $tags);
        $this->assertEquals('webmozart/tag1', $tags[0]);
        $this->assertEquals('webmozart/tag2', $tags[1]);
    }

    public function testGetTagsReturnsSortedResult()
    {
        $repo = $this->createRepository(
            new TestDirectory('/', array(
                new TestDirectory('/webmozart', array(
                    new TestDirectory('/webmozart/puli', array(
                        new TestFile('/webmozart/puli/file1'),
                        new TestFile('/webmozart/puli/file2'),
                    )),
                )),
            )),
            array(
                '/webmozart/puli/file1' => 'webmozart/foo',
                '/webmozart/puli/file2' => 'webmozart/bar',
            )
        );

        $tags = $repo->getTags();

        $this->assertCount(2, $tags);
        $this->assertEquals('webmozart/bar', $tags[0]);
        $this->assertEquals('webmozart/foo', $tags[1]);
    }
}
