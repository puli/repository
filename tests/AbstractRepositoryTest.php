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

use PHPUnit_Framework_TestCase;
use Puli\Repository\Resource\DirectoryResource;
use Puli\Repository\ResourceRepository;
use Puli\Repository\Tests\Resource\TestDirectory;
use Puli\Repository\Tests\Resource\TestFile;

/**
 * @since  1.0
 * @author Bernhard Schussek <bschussek@gmail.com>
 */
abstract class AbstractRepositoryTest extends PHPUnit_Framework_TestCase
{
    /**
     * @param DirectoryResource $root
     *
     * @return ResourceRepository
     */
    abstract protected function createRepository(DirectoryResource $root);

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

        $this->assertTrue($repo->contains('/*'));
        $this->assertTrue($repo->contains('/webmozart/*'));
        $this->assertFalse($repo->contains('/webmozart/file*'));
        $this->assertTrue($repo->contains('/webmozart/puli/file*'));
        $this->assertTrue($repo->contains('/*file*'));
        $this->assertTrue($repo->contains('/webmozart/*file*'));
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
     * @expectedException \InvalidArgumentException
     */
    public function testContainsExpectsAbsolutePath()
    {
        $repo = $this->createRepository(new TestDirectory('/', array(
            new TestDirectory('/webmozart'),
        )));

        $repo->contains('webmozart');
    }

    /**
     * @expectedException \InvalidArgumentException
     */
    public function testContainsExpectsNonEmptyPath()
    {
        $repo = $this->createRepository(new TestDirectory('/'));

        $repo->contains('');
    }

    /**
     * @expectedException \InvalidArgumentException
     */
    public function testContainsExpectsStringPath()
    {
        $repo = $this->createRepository(new TestDirectory('/'));

        $repo->contains(new \stdClass());
    }

    public function testGetFile()
    {
        $repo = $this->createRepository(new TestDirectory('/', array(
            new TestDirectory('/webmozart', array(
                new TestDirectory('/webmozart/puli', array(
                    new TestFile('/webmozart/puli/file'),
                )),
            )),
        )));

        $file = $repo->get('/webmozart/puli/file');

        $this->assertInstanceOf('Puli\Repository\Resource\FileResource', $file);
        $this->assertSame('/webmozart/puli/file', $file->getPath());
        $this->assertSame($repo, $file->getRepository());
        $this->assertSame(1, $file->getVersion());
    }

    public function testGetDirectory()
    {
        $repo = $this->createRepository(new TestDirectory('/', array(
            new TestDirectory('/webmozart', array(
                new TestDirectory('/webmozart/puli'),
            )),
        )));

        $dir = $repo->get('/webmozart/puli');

        $this->assertInstanceOf('Puli\Repository\Resource\DirectoryResource', $dir);
        $this->assertSame('/webmozart/puli', $dir->getPath());
        $this->assertSame($repo, $dir->getRepository());
        $this->assertSame(1, $dir->getVersion());
    }

    public function testGetDiscardsTrailingSlash()
    {
        $repo = $this->createRepository(new TestDirectory('/', array(
            new TestDirectory('/webmozart'),
        )));

        $this->assertEquals($repo->get('/webmozart'), $repo->get('/webmozart/'));
    }

    public function testGetInterpretsConsecutiveSlashesAsRoot()
    {
        $repo = $this->createRepository(new TestDirectory('/'));

        $this->assertEquals($repo->get('/'), $repo->get('///'));
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

        $this->assertEquals($repo->get('/webmozart/puli/file'), $repo->get('/webmozart/puli/../puli/./file'));
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

        $this->assertEquals($repo->get('/webmozart/puli/dir'), $repo->get('/webmozart/puli/../puli/dir'));
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
     * @expectedException \Puli\Repository\ResourceNotFoundException
     */
    public function testGetExpectsExistingVersion()
    {
        $repo = $this->createRepository(new TestDirectory('/', array(
            new TestFile('/webmozart'),
        )));

        $repo->get('/webmozart', 2);
    }

    /**
     * @expectedException \InvalidArgumentException
     */
    public function testGetExpectsAbsolutePath()
    {
        $repo = $this->createRepository(new TestDirectory('/', array(
            new TestDirectory('/webmozart'),
        )));

        $repo->get('webmozart');
    }

    /**
     * @expectedException \InvalidArgumentException
     */
    public function testGetExpectsNonEmptyPath()
    {
        $repo = $this->createRepository(new TestDirectory('/'));

        $repo->get('');
    }

    /**
     * @expectedException \InvalidArgumentException
     */
    public function testGetExpectsStringPath()
    {
        $repo = $this->createRepository(new TestDirectory('/'));

        $repo->get(new \stdClass());
    }

    public function testGetDotInDirectory()
    {
        $repo = $this->createRepository(new TestDirectory('/', array(
            new TestDirectory('/webmozart'),
        )));

        $this->assertEquals($repo->get('/webmozart'), $repo->get('/webmozart/.'));
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
        $this->assertEquals($repo->get('/webmozart/puli/file1'), $repo->get('/webmozart/puli/file1/.'));
    }

    public function testGetDotInRoot()
    {
        $repo = $this->createRepository(new TestDirectory('/'));

        $this->assertEquals($repo->get('/'), $repo->get('/.'));
    }

    public function testGetDotDotInDirectory()
    {
        $repo = $this->createRepository(new TestDirectory('/', array(
            new TestDirectory('/webmozart', array(
                new TestDirectory('/webmozart/puli'),
            )),
        )));

        $this->assertEquals($repo->get('/webmozart'), $repo->get('/webmozart/puli/..'));
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
        $this->assertEquals($repo->get('/webmozart/puli'), $repo->get('/webmozart/puli/file1/..'));
    }

    public function testGetDotDotInRoot()
    {
        $repo = $this->createRepository(new TestDirectory('/'));

        $this->assertEquals($repo->get('/'), $repo->get('/..'));
    }

    public function testListDirectory()
    {
        $repo = $this->createRepository(new TestDirectory('/', array(
            new TestDirectory('/webmozart', array(
                new TestDirectory('/webmozart/puli', array(
                    new TestFile('/webmozart/puli/.dotfile'),
                    new TestFile('/webmozart/puli/foo'),
                    new TestFile('/webmozart/puli/bar'),
                    new TestDirectory('/webmozart/puli/dir', array(
                        // Nest another directory which matches the regex
                        // /webmozart/puli/[^/]+$
                        new TestDirectory('/webmozart/puli/dir/webmozart', array(
                            new TestDirectory('/webmozart/puli/dir/webmozart/puli', array(
                                new TestFile('/webmozart/puli/dir/webmozart/puli/file'),
                            ))
                        )),
                    )),
                )),
            )),
        )));

        $resources = $repo->listDirectory('/webmozart/puli');

        $this->assertCount(4, $resources);
        $this->assertInstanceOf('Puli\Repository\Resource\Collection\ResourceCollection', $resources);
        // sorted
        $this->assertEquals($repo->get('/webmozart/puli/.dotfile'), $resources[0]);
        $this->assertEquals($repo->get('/webmozart/puli/bar'), $resources[1]);
        $this->assertEquals($repo->get('/webmozart/puli/dir'), $resources[2]);
        $this->assertEquals($repo->get('/webmozart/puli/foo'), $resources[3]);
    }

    public function testListRootDirectory()
    {
        $repo = $this->createRepository(new TestDirectory('/', array(
            new TestDirectory('/webmozart'),
            new TestDirectory('/acme'),
        )));

        $resources = $repo->listDirectory('/');

        $this->assertCount(2, $resources);
        $this->assertInstanceOf('Puli\Repository\Resource\Collection\ResourceCollection', $resources);
        // sorted
        $this->assertEquals($repo->get('/acme'), $resources[0]);
        $this->assertEquals($repo->get('/webmozart'), $resources[1]);
    }

    /**
     * @expectedException \Puli\Repository\ResourceNotFoundException
     */
    public function testListDirectoryExpectsExistingResource()
    {
        $repo = $this->createRepository(new TestDirectory('/'));

        $repo->listDirectory('/foo/bar');
    }

    /**
     * @expectedException \Puli\Repository\NoDirectoryException
     */
    public function testListDirectoryExpectsDirectoryResource()
    {
        $repo = $this->createRepository(new TestDirectory('/', array(
            new TestFile('/webmozart'),
        )));

        $repo->listDirectory('/webmozart');
    }

    /**
     * @expectedException \InvalidArgumentException
     */
    public function testListDirectoryExpectsAbsolutePath()
    {
        $repo = $this->createRepository(new TestDirectory('/', array(
            new TestDirectory('/webmozart'),
        )));

        $repo->listDirectory('webmozart');
    }

    /**
     * @expectedException \InvalidArgumentException
     */
    public function testListDirectoryExpectsNonEmptyPath()
    {
        $repo = $this->createRepository(new TestDirectory('/'));

        $repo->listDirectory('');
    }

    /**
     * @expectedException \InvalidArgumentException
     */
    public function testListDirectoryExpectsStringPath()
    {
        $repo = $this->createRepository(new TestDirectory('/'));

        $repo->listDirectory(new \stdClass());
    }

    public function testFind()
    {
        $repo = $this->createRepository(new TestDirectory('/', array(
            new TestDirectory('/webmozart', array(
                new TestDirectory('/webmozart/puli', array(
                    new TestFile('/webmozart/puli/.dotfoo'),
                    new TestFile('/webmozart/puli/foo'),
                    new TestFile('/webmozart/puli/bar'),
                    new TestDirectory('/webmozart/puli/dirfoo'),
                )),
            )),
        )));

        $resources = $repo->find('/webmozart/*foo');

        $this->assertCount(3, $resources);
        $this->assertInstanceOf('Puli\Repository\Resource\Collection\ResourceCollection', $resources);
        // sorted
        $this->assertEquals($repo->get('/webmozart/puli/.dotfoo'), $resources[0]);
        $this->assertEquals($repo->get('/webmozart/puli/dirfoo'), $resources[1]);
        $this->assertEquals($repo->get('/webmozart/puli/foo'), $resources[2]);
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
        $this->assertInstanceOf('Puli\Repository\Resource\Collection\ResourceCollection', $resources);
        $this->assertEquals($repo->get('/webmozart/puli/file'), $resources[0]);
    }

    public function testFindDirectory()
    {
        $repo = $this->createRepository(new TestDirectory('/', array(
            new TestDirectory('/webmozart'),
        )));

        $resources = $repo->find('/webmozart');

        $this->assertCount(1, $resources);
        $this->assertInstanceOf('Puli\Repository\Resource\Collection\ResourceCollection', $resources);
        $this->assertEquals($repo->get('/webmozart'), $resources[0]);
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
        $this->assertInstanceOf('Puli\Repository\Resource\Collection\ResourceCollection', $resources);
        $this->assertEquals($repo->get('/webmozart/puli/file1'), $resources[0]);
    }

    public function testFindNoMatches()
    {
        $repo = $this->createRepository(new TestDirectory('/'));

        $resources = $repo->find('/foo/*');

        $this->assertCount(0, $resources);
        $this->assertInstanceOf('Puli\Repository\Resource\Collection\ResourceCollection', $resources);
    }

    /**
     * @expectedException \InvalidArgumentException
     */
    public function testFindExpectsAbsolutePath()
    {
        $repo = $this->createRepository(new TestDirectory('/'));

        $repo->find('*');
    }

    /**
     * @expectedException \InvalidArgumentException
     */
    public function testFindExpectsNonEmptyPath()
    {
        $repo = $this->createRepository(new TestDirectory('/'));

        $repo->find('');
    }

    /**
     * @expectedException \InvalidArgumentException
     */
    public function testFindExpectsStringPath()
    {
        $repo = $this->createRepository(new TestDirectory('/'));

        $repo->find(new \stdClass());
    }
}
