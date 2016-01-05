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

use Puli\Repository\Api\EditableRepository;
use Puli\Repository\ChangeStream\InMemoryChangeStream;
use Puli\Repository\InMemoryRepository;
use Puli\Repository\Resource\Collection\ArrayResourceCollection;
use Puli\Repository\Resource\DirectoryResource;
use Puli\Repository\Resource\FileResource;
use Puli\Repository\Resource\LinkResource;
use Puli\Repository\Tests\Resource\TestDirectory;
use Puli\Repository\Tests\Resource\TestFile;

/**
 * @author Bernhard Schussek <bschussek@gmail.com>
 */
abstract class AbstractEditableRepositoryTest extends AbstractRepositoryTest
{
    /**
     * The instance used to change the contents of the repository.
     *
     * @var EditableRepository
     */
    protected $writeRepo;

    /**
     * The instance used to check the changed contents of the repository.
     *
     * This is either the same instance as {@link $writeRepo} or a different
     * instance that uses the same data source.
     *
     * @var EditableRepository
     */
    protected $readRepo;

    /**
     * @var InMemoryChangeStream
     */
    protected $stream;

    private static $symlinkOnWindows = null;

    public static function setUpBeforeClass()
    {
        // Detect whether symlinks are supported on Windows (it requires enough privileges)
        // This logic is copied from the Symfony Filesystem component testsuite
        if ('\\' === DIRECTORY_SEPARATOR && null === self::$symlinkOnWindows) {
            $target = tempnam(sys_get_temp_dir(), 'sl');
            $link = sys_get_temp_dir().'/sl'.microtime(true).mt_rand();
            if (self::$symlinkOnWindows = @symlink($target, $link)) {
                unlink($link);
            }
            unlink($target);
        }
    }

    /**
     * @return EditableRepository
     */
    abstract protected function createWriteRepository();

    /**
     * @param EditableRepository $writeRepo
     *
     * @return EditableRepository
     */
    abstract protected function createReadRepository(EditableRepository $writeRepo);

    protected function setUp()
    {
        parent::setUp();

        $this->stream = new InMemoryChangeStream();
        $this->writeRepo = $this->createWriteRepository();
        $this->readRepo = $this->createReadRepository($this->writeRepo);
    }

    protected function markAsSkippedIfSymlinkIsMissing()
    {
        if (!function_exists('symlink')) {
            $this->markTestSkipped('symlink is not supported');
        }

        if ('\\' === DIRECTORY_SEPARATOR && false === self::$symlinkOnWindows) {
            $this->markTestSkipped('symlink requires "Create symbolic links" privilege on windows');
        }
    }

    public function testRootIsEmptyBeforeAdding()
    {
        $root = $this->readRepo->get('/');

        $this->assertInstanceOf('Puli\Repository\Api\Resource\PuliResource', $root);
        $this->assertCount(0, $root->listChildren());
        $this->assertSame('/', $root->getPath());
    }

    public function testAddFile()
    {
        $this->writeRepo->add('/webmozart/puli', $this->prepareFixtures($this->createDirectory()));
        $this->writeRepo->add('/webmozart/puli/file', $this->prepareFixtures($this->createFile()));

        $dir = $this->readRepo->get('/webmozart/puli');
        $file = $this->readRepo->get('/webmozart/puli/file');

        $this->assertInstanceOf('Puli\Repository\Api\Resource\PuliResource', $dir);
        $this->assertSame('/webmozart/puli', $dir->getPath());
        $this->assertSame($this->readRepo, $dir->getRepository());

        $this->assertInstanceOf('Puli\Repository\Api\Resource\BodyResource', $file);
        $this->assertSame('/webmozart/puli/file', $file->getPath());
        $this->assertSame($this->readRepo, $file->getRepository());
        $this->assertSame(TestFile::BODY, $file->getBody());
    }

    public function testAddDoesNotAttachResourceToRepository()
    {
        $directory = $this->prepareFixtures($this->createDirectory('/dir', array(
            $this->createFile('/file1'),
            $this->createFile('/file2'),
        )));
        $file1 = $directory->getChild('file1');
        $file2 = $directory->getChild('file2');

        $this->writeRepo->add('/webmozart', $directory);

        $this->assertNull($directory->getRepository());
        $this->assertNull($file1->getRepository());
        $this->assertNull($file2->getRepository());

        $directory = $this->readRepo->get('/webmozart');
        $file1 = $this->readRepo->get('/webmozart/file1');
        $file2 = $this->readRepo->get('/webmozart/file2');

        $this->assertSame($this->readRepo, $directory->getRepository());
        $this->assertSame($this->readRepo, $file1->getRepository());
        $this->assertSame($this->readRepo, $file2->getRepository());
    }

    public function testAddDoesNotChangeAttachedRepository()
    {
        $otherRepo = new InMemoryRepository();
        $otherRepo->add('/dir', $this->prepareFixtures($this->createDirectory('/', array(
            $this->createFile('/file1'),
            $this->createFile('/file2'),
        ))));

        $directory = $otherRepo->get('/dir');
        $file1 = $otherRepo->get('/dir/file1');
        $file2 = $otherRepo->get('/dir/file2');

        $this->writeRepo->add('/webmozart', $directory);

        $this->assertSame($otherRepo, $directory->getRepository());
        $this->assertSame($otherRepo, $file1->getRepository());
        $this->assertSame($otherRepo, $file2->getRepository());

        $directory = $this->readRepo->get('/webmozart');
        $file1 = $this->readRepo->get('/webmozart/file1');
        $file2 = $this->readRepo->get('/webmozart/file2');

        $this->assertSame($this->readRepo, $directory->getRepository());
        $this->assertSame($this->readRepo, $file1->getRepository());
        $this->assertSame($this->readRepo, $file2->getRepository());
    }

    public function testAddMergesResourceChildren()
    {
        $this->writeRepo->add('/webmozart/puli', $this->prepareFixtures($this->createDirectory('/foo', array(
            $this->createFile('/file1', 'original 1'),
            $this->createFile('/file2', 'original 2'),
        ))));

        $this->writeRepo->add('/webmozart/puli', $this->prepareFixtures($this->createDirectory('/bar', array(
            $this->createFile('/file1', 'override 1'),
            $this->createFile('/file3', 'override 3'),
        ))));

        $dir = $this->readRepo->get('/webmozart/puli');
        $file1 = $this->readRepo->get('/webmozart/puli/file1');
        $file2 = $this->readRepo->get('/webmozart/puli/file2');
        $file3 = $this->readRepo->get('/webmozart/puli/file3');

        $this->assertTrue($this->readRepo->hasChildren('/webmozart/puli'));
        $this->assertCount(3, $this->readRepo->listChildren('/webmozart/puli'));

        $this->assertInstanceOf('Puli\Repository\Api\Resource\PuliResource', $dir);
        $this->assertSame('/webmozart/puli', $dir->getPath());

        $this->assertInstanceOf('Puli\Repository\Api\Resource\BodyResource', $file1);
        $this->assertSame('/webmozart/puli/file1', $file1->getPath());
        $this->assertSame('override 1', $file1->getBody());

        $this->assertInstanceOf('Puli\Repository\Api\Resource\BodyResource', $file2);
        $this->assertSame('/webmozart/puli/file2', $file2->getPath());
        $this->assertSame('original 2', $file2->getBody());

        $this->assertInstanceOf('Puli\Repository\Api\Resource\BodyResource', $file3);
        $this->assertSame('/webmozart/puli/file3', $file3->getPath());
        $this->assertSame('override 3', $file3->getBody());
    }

    public function testAddDot()
    {
        $this->writeRepo->add('/webmozart/puli/file/.', $this->prepareFixtures($this->createFile()));

        $file = $this->readRepo->get('/webmozart/puli/file');

        $this->assertInstanceOf('Puli\Repository\Api\Resource\BodyResource', $file);
        $this->assertSame('/webmozart/puli/file', $file->getPath());
    }

    public function testAddDotDot()
    {
        $this->writeRepo->add('/webmozart/puli/file/..', $this->prepareFixtures($this->createFile()));

        $file = $this->readRepo->get('/webmozart/puli');

        $this->assertInstanceOf('Puli\Repository\Api\Resource\BodyResource', $file);
        $this->assertSame('/webmozart/puli', $file->getPath());
    }

    public function testAddTrimsTrailingSlash()
    {
        $this->writeRepo->add('/webmozart/puli/file/', $this->prepareFixtures($this->createFile()));

        $file = $this->readRepo->get('/webmozart/puli/file');

        $this->assertInstanceOf('Puli\Repository\Api\Resource\BodyResource', $file);
        $this->assertSame('/webmozart/puli/file', $file->getPath());
    }

    public function testAddCollection()
    {
        $this->writeRepo->add('/webmozart/puli', new ArrayResourceCollection(array(
            $this->prepareFixtures($this->createFile('/file1')),
            $this->prepareFixtures($this->createFile('/file2')),
        )));

        $file1 = $this->readRepo->get('/webmozart/puli/file1');
        $file2 = $this->readRepo->get('/webmozart/puli/file2');

        $this->assertInstanceOf('Puli\Repository\Api\Resource\BodyResource', $file1);
        $this->assertSame('/webmozart/puli/file1', $file1->getPath());

        $this->assertInstanceOf('Puli\Repository\Api\Resource\BodyResource', $file2);
        $this->assertSame('/webmozart/puli/file2', $file2->getPath());
    }

    public function testAddRoot()
    {
        $this->writeRepo->add('/', $this->prepareFixtures($this->createDirectory('/', array(
            $this->createDirectory('/webmozart', array(
                $this->createFile('/file'),
            )),
        ))));

        $root = $this->readRepo->get('/');
        $dir = $this->readRepo->get('/webmozart');
        $file = $this->readRepo->get('/webmozart/file');

        $this->assertInstanceOf('Puli\Repository\Api\Resource\PuliResource', $root);
        $this->assertSame('/', $root->getPath());
        $this->assertSame($this->readRepo, $root->getRepository());
        $this->assertCount(1, $root->listChildren());

        $this->assertInstanceOf('Puli\Repository\Api\Resource\PuliResource', $dir);
        $this->assertSame('/webmozart', $dir->getPath());
        $this->assertSame($this->readRepo, $dir->getRepository());
        $this->assertCount(1, $dir->listChildren());

        $this->assertInstanceOf('Puli\Repository\Api\Resource\BodyResource', $file);
        $this->assertSame('/webmozart/file', $file->getPath());
        $this->assertSame($this->readRepo, $file->getRepository());
        $this->assertSame(TestFile::BODY, $file->getBody());
    }

    /**
     * @expectedException \InvalidArgumentException
     */
    public function testAddExpectsAbsolutePath()
    {
        $this->writeRepo->add('webmozart', $this->prepareFixtures($this->createDirectory()));
    }

    /**
     * @expectedException \InvalidArgumentException
     */
    public function testAddExpectsNonEmptyPath()
    {
        $this->writeRepo->add('', $this->prepareFixtures($this->createDirectory()));
    }

    /**
     * @expectedException \InvalidArgumentException
     */
    public function testAddExpectsStringPath()
    {
        $this->writeRepo->add(new \stdClass(), $this->prepareFixtures($this->createDirectory()));
    }

    /**
     * @expectedException \Puli\Repository\Api\UnsupportedResourceException
     */
    public function testAddExpectsResource()
    {
        $this->writeRepo->add('/webmozart', new \stdClass());
    }

    public function testOverride()
    {
        $this->writeRepo->add('/webmozart/file', $this->prepareFixtures($this->createFile('/file1', 'BODY0')));
        $this->writeRepo->add('/webmozart/file', $this->prepareFixtures($this->createFile('/file2', 'BODY1')));
        $this->writeRepo->add('/webmozart/file', $this->prepareFixtures($this->createFile('/file3', 'BODY2')));

        $versions = $this->readRepo->getVersions('/webmozart/file');

        $this->assertSame(array(0, 1, 2), $versions->getVersions());

        $v0 = $versions->get(0);

        $this->assertInstanceOf('Puli\Repository\Api\Resource\BodyResource', $v0);
        $this->assertSame('/webmozart/file', $v0->getPath());
        $this->assertSame($this->readRepo, $v0->getRepository());
        $this->assertSame('BODY0', $v0->getBody());

        $v1 = $versions->get(1);

        $this->assertInstanceOf('Puli\Repository\Api\Resource\BodyResource', $v1);
        $this->assertSame('/webmozart/file', $v1->getPath());
        $this->assertSame($this->readRepo, $v1->getRepository());
        $this->assertSame('BODY1', $v1->getBody());

        $v2 = $versions->get(2);

        $this->assertInstanceOf('Puli\Repository\Api\Resource\BodyResource', $v2);
        $this->assertSame('/webmozart/file', $v2->getPath());
        $this->assertSame($this->readRepo, $v2->getRepository());
        $this->assertSame('BODY2', $v2->getBody());
    }

    public function testOverrideSuperPath()
    {
        $this->writeRepo->add('/webmozart/puli/file', $this->prepareFixtures($this->createFile('/file', 'BODY0')));

        $this->writeRepo->add('/webmozart/puli', $this->prepareFixtures($this->createDirectory('/dir1', array(
            $this->createFile('/file', 'BODY1'),
        ))));

        $this->writeRepo->add('/webmozart', $this->prepareFixtures($this->createDirectory('/dir2', array(
            $this->createDirectory('/puli', array(
                $this->createFile('/file', 'BODY2'),
            )),
        ))));

        $versions = $this->readRepo->getVersions('/webmozart/puli/file');

        $this->assertSame(array(0, 1, 2), $versions->getVersions());

        $v0 = $versions->get(0);

        $this->assertInstanceOf('Puli\Repository\Api\Resource\BodyResource', $v0);
        $this->assertSame('/webmozart/puli/file', $v0->getPath());
        $this->assertSame($this->readRepo, $v0->getRepository());
        $this->assertSame('BODY0', $v0->getBody());

        $v1 = $versions->get(1);

        $this->assertInstanceOf('Puli\Repository\Api\Resource\BodyResource', $v1);
        $this->assertSame('/webmozart/puli/file', $v1->getPath());
        $this->assertSame($this->readRepo, $v1->getRepository());
        $this->assertSame('BODY1', $v1->getBody());

        $v2 = $versions->get(2);

        $this->assertInstanceOf('Puli\Repository\Api\Resource\BodyResource', $v2);
        $this->assertSame('/webmozart/puli/file', $v2->getPath());
        $this->assertSame($this->readRepo, $v2->getRepository());
        $this->assertSame('BODY2', $v2->getBody());
    }

    public function testOverrideSubPath()
    {
        $this->writeRepo->add('/webmozart', $this->prepareFixtures($this->createDirectory('/dir1', array(
            $this->createDirectory('/puli', array(
                $this->createFile('/file', 'BODY0'),
            )),
        ))));

        $this->writeRepo->add('/webmozart/puli', $this->prepareFixtures($this->createDirectory('/dir2', array(
            $this->createFile('/file', 'BODY1'),
        ))));

        $this->writeRepo->add('/webmozart/puli/file', $this->prepareFixtures($this->createFile('/file', 'BODY2')));

        $versions = $this->readRepo->getVersions('/webmozart/puli/file');

        $this->assertSame(array(0, 1, 2), $versions->getVersions());

        $v0 = $versions->get(0);

        $this->assertInstanceOf('Puli\Repository\Api\Resource\BodyResource', $v0);
        $this->assertSame('/webmozart/puli/file', $v0->getPath());
        $this->assertSame($this->readRepo, $v0->getRepository());
        $this->assertSame('BODY0', $v0->getBody());

        $v1 = $versions->get(1);

        $this->assertInstanceOf('Puli\Repository\Api\Resource\BodyResource', $v1);
        $this->assertSame('/webmozart/puli/file', $v1->getPath());
        $this->assertSame($this->readRepo, $v1->getRepository());
        $this->assertSame('BODY1', $v1->getBody());

        $v2 = $versions->get(2);

        $this->assertInstanceOf('Puli\Repository\Api\Resource\BodyResource', $v2);
        $this->assertSame('/webmozart/puli/file', $v2->getPath());
        $this->assertSame($this->readRepo, $v2->getRepository());
        $this->assertSame('BODY2', $v2->getBody());
    }

    /**
     * @depends testOverrideSuperPath
     * @depends testOverrideSubPath
     */
    public function testOverrideSuperAndSubPathShortFirst()
    {
        $this->writeRepo->add('/webmozart', $this->prepareFixtures($this->createDirectory('/dir2', array(
            $this->createDirectory('/puli', array(
                $this->createFile('/file', 'BODY0'),
            )),
        ))));

        $this->writeRepo->add('/webmozart/puli', $this->prepareFixtures($this->createDirectory('/dir1', array(
            $this->createFile('/file', 'BODY1'),
        ))));

        $this->writeRepo->add('/webmozart/puli/file', $this->prepareFixtures($this->createFile('/file', 'BODY2')));

        $this->writeRepo->add('/webmozart', $this->prepareFixtures($this->createDirectory('/dir3', array(
            $this->createDirectory('/puli', array(
                $this->createFile('/file', 'BODY3'),
            )),
        ))));

        $versions = $this->readRepo->getVersions('/webmozart/puli/file');

        $this->assertSame(array(0, 1, 2, 3), $versions->getVersions());

        $v0 = $versions->get(0);

        $this->assertInstanceOf('Puli\Repository\Api\Resource\BodyResource', $v0);
        $this->assertSame('/webmozart/puli/file', $v0->getPath());
        $this->assertSame($this->readRepo, $v0->getRepository());
        $this->assertSame('BODY0', $v0->getBody());

        $v1 = $versions->get(1);

        $this->assertInstanceOf('Puli\Repository\Api\Resource\BodyResource', $v1);
        $this->assertSame('/webmozart/puli/file', $v1->getPath());
        $this->assertSame($this->readRepo, $v1->getRepository());
        $this->assertSame('BODY1', $v1->getBody());

        $v2 = $versions->get(2);

        $this->assertInstanceOf('Puli\Repository\Api\Resource\BodyResource', $v2);
        $this->assertSame('/webmozart/puli/file', $v2->getPath());
        $this->assertSame($this->readRepo, $v2->getRepository());
        $this->assertSame('BODY2', $v2->getBody());

        $v3 = $versions->get(3);

        $this->assertInstanceOf('Puli\Repository\Api\Resource\BodyResource', $v3);
        $this->assertSame('/webmozart/puli/file', $v3->getPath());
        $this->assertSame($this->readRepo, $v3->getRepository());
        $this->assertSame('BODY3', $v3->getBody());
    }

    /**
     * @depends testOverrideSuperPath
     * @depends testOverrideSubPath
     */
    public function testOverrideSuperAndSubPathMediumFirst()
    {
        $this->writeRepo->add('/webmozart/puli', $this->prepareFixtures($this->createDirectory('/dir1', array(
            $this->createFile('/file', 'BODY0'),
        ))));

        $this->writeRepo->add('/webmozart', $this->prepareFixtures($this->createDirectory('/dir2', array(
            $this->createDirectory('/puli', array(
                $this->createFile('/file', 'BODY1'),
            )),
        ))));

        $this->writeRepo->add('/webmozart/puli/file', $this->prepareFixtures($this->createFile('/file', 'BODY2')));

        $this->writeRepo->add('/webmozart', $this->prepareFixtures($this->createDirectory('/dir3', array(
            $this->createDirectory('/puli', array(
                $this->createFile('/file', 'BODY3'),
            )),
        ))));

        $versions = $this->readRepo->getVersions('/webmozart/puli/file');

        $this->assertSame(array(0, 1, 2, 3), $versions->getVersions());

        $v0 = $versions->get(0);

        $this->assertInstanceOf('Puli\Repository\Api\Resource\BodyResource', $v0);
        $this->assertSame('/webmozart/puli/file', $v0->getPath());
        $this->assertSame($this->readRepo, $v0->getRepository());
        $this->assertSame('BODY0', $v0->getBody());

        $v1 = $versions->get(1);

        $this->assertInstanceOf('Puli\Repository\Api\Resource\BodyResource', $v1);
        $this->assertSame('/webmozart/puli/file', $v1->getPath());
        $this->assertSame($this->readRepo, $v1->getRepository());
        $this->assertSame('BODY1', $v1->getBody());

        $v2 = $versions->get(2);

        $this->assertInstanceOf('Puli\Repository\Api\Resource\BodyResource', $v2);
        $this->assertSame('/webmozart/puli/file', $v2->getPath());
        $this->assertSame($this->readRepo, $v2->getRepository());
        $this->assertSame('BODY2', $v2->getBody());

        $v3 = $versions->get(3);

        $this->assertInstanceOf('Puli\Repository\Api\Resource\BodyResource', $v3);
        $this->assertSame('/webmozart/puli/file', $v3->getPath());
        $this->assertSame($this->readRepo, $v3->getRepository());
        $this->assertSame('BODY3', $v3->getBody());
    }

    /**
     * @depends testOverrideSuperPath
     * @depends testOverrideSubPath
     */
    public function testOverrideSuperAndSubPathLongFirst()
    {
        $this->writeRepo->add('/webmozart/puli/file', $this->prepareFixtures($this->createFile('/file', 'BODY0')));

        $this->writeRepo->add('/webmozart', $this->prepareFixtures($this->createDirectory('/dir2', array(
            $this->createDirectory('/puli', array(
                $this->createFile('/file', 'BODY1'),
            )),
        ))));

        $this->writeRepo->add('/webmozart/puli', $this->prepareFixtures($this->createDirectory('/dir1', array(
            $this->createFile('/file', 'BODY2'),
        ))));

        $this->writeRepo->add('/webmozart', $this->prepareFixtures($this->createDirectory('/dir3', array(
            $this->createDirectory('/puli', array(
                $this->createFile('/file', 'BODY3'),
            )),
        ))));

        $versions = $this->readRepo->getVersions('/webmozart/puli/file');

        $this->assertSame(array(0, 1, 2, 3), $versions->getVersions());

        $v0 = $versions->get(0);

        $this->assertInstanceOf('Puli\Repository\Api\Resource\BodyResource', $v0);
        $this->assertSame('/webmozart/puli/file', $v0->getPath());
        $this->assertSame($this->readRepo, $v0->getRepository());
        $this->assertSame('BODY0', $v0->getBody());

        $v1 = $versions->get(1);

        $this->assertInstanceOf('Puli\Repository\Api\Resource\BodyResource', $v1);
        $this->assertSame('/webmozart/puli/file', $v1->getPath());
        $this->assertSame($this->readRepo, $v1->getRepository());
        $this->assertSame('BODY1', $v1->getBody());

        $v2 = $versions->get(2);

        $this->assertInstanceOf('Puli\Repository\Api\Resource\BodyResource', $v2);
        $this->assertSame('/webmozart/puli/file', $v2->getPath());
        $this->assertSame($this->readRepo, $v2->getRepository());
        $this->assertSame('BODY2', $v2->getBody());

        $v3 = $versions->get(3);

        $this->assertInstanceOf('Puli\Repository\Api\Resource\BodyResource', $v3);
        $this->assertSame('/webmozart/puli/file', $v3->getPath());
        $this->assertSame($this->readRepo, $v3->getRepository());
        $this->assertSame('BODY3', $v3->getBody());
    }

    /**
     * @ depends testOverrideSuperPath
     * @ depends testOverrideSubPath
     */
    public function testOverrideFourLevels()
    {
        $this->writeRepo->add('/webmozart/puli', $this->prepareFixtures($this->createDirectory('/dir1', array(
            $this->createDirectory('/sub', array(
                $this->createFile('/file', 'BODY0'),
            )),
        ))));

        $this->writeRepo->add('/webmozart/puli/sub', $this->prepareFixtures($this->createDirectory('/dir2', array(
            $this->createFile('/file', 'BODY1'),
        ))));

        $this->writeRepo->add('/webmozart/puli/sub/file', $this->prepareFixtures($this->createFile('/file', 'BODY2')));

        $this->writeRepo->add('/webmozart', $this->prepareFixtures($this->createDirectory('/dir3', array(
            $this->createDirectory('/puli', array(
                $this->createDirectory('/sub', array(
                    $this->createFile('/file', 'BODY3'),
                )),
            )),
        ))));

        $versions = $this->readRepo->getVersions('/webmozart/puli/sub/file');

        $this->assertSame(array(0, 1, 2, 3), $versions->getVersions());

        $v0 = $versions->get(0);

        $this->assertInstanceOf('Puli\Repository\Api\Resource\BodyResource', $v0);
        $this->assertSame('/webmozart/puli/sub/file', $v0->getPath());
        $this->assertSame($this->readRepo, $v0->getRepository());
        $this->assertSame('BODY0', $v0->getBody());

        $v1 = $versions->get(1);

        $this->assertInstanceOf('Puli\Repository\Api\Resource\BodyResource', $v1);
        $this->assertSame('/webmozart/puli/sub/file', $v1->getPath());
        $this->assertSame($this->readRepo, $v1->getRepository());
        $this->assertSame('BODY1', $v1->getBody());

        $v2 = $versions->get(2);

        $this->assertInstanceOf('Puli\Repository\Api\Resource\BodyResource', $v2);
        $this->assertSame('/webmozart/puli/sub/file', $v2->getPath());
        $this->assertSame($this->readRepo, $v2->getRepository());
        $this->assertSame('BODY2', $v2->getBody());

        $v3 = $versions->get(3);

        $this->assertInstanceOf('Puli\Repository\Api\Resource\BodyResource', $v3);
        $this->assertSame('/webmozart/puli/sub/file', $v3->getPath());
        $this->assertSame($this->readRepo, $v3->getRepository());
        $this->assertSame('BODY3', $v3->getBody());
    }

    public function testRemoveFile()
    {
        $this->writeRepo->add('/webmozart/puli/file1', $this->prepareFixtures($this->createFile()));
        $this->writeRepo->add('/webmozart/puli/file2', $this->prepareFixtures($this->createFile()));

        $this->assertTrue($this->readRepo->contains('/webmozart'));
        $this->assertTrue($this->readRepo->contains('/webmozart/puli'));
        $this->assertTrue($this->readRepo->contains('/webmozart/puli/file1'));
        $this->assertTrue($this->readRepo->contains('/webmozart/puli/file2'));

        $this->assertSame(1, $this->writeRepo->remove('/webmozart/puli/file1'));

        $this->readRepo = $this->createReadRepository($this->writeRepo);

        $this->assertTrue($this->readRepo->contains('/webmozart'));
        $this->assertTrue($this->readRepo->contains('/webmozart/puli'));
        $this->assertFalse($this->readRepo->contains('/webmozart/puli/file1'));
        $this->assertTrue($this->readRepo->contains('/webmozart/puli/file2'));
    }

    public function testRemoveMany()
    {
        $this->writeRepo->add('/webmozart/puli/file1', $this->prepareFixtures($this->createFile()));
        $this->writeRepo->add('/webmozart/puli/file2', $this->prepareFixtures($this->createFile()));

        $this->assertTrue($this->readRepo->contains('/webmozart/puli'));
        $this->assertTrue($this->readRepo->contains('/webmozart/puli/file1'));
        $this->assertTrue($this->readRepo->contains('/webmozart/puli/file2'));

        $this->assertSame(2, $this->writeRepo->remove('/webmozart/puli/file*'));

        $this->readRepo = $this->createReadRepository($this->writeRepo);

        $this->assertTrue($this->readRepo->contains('/webmozart/puli'));
        $this->assertFalse($this->readRepo->contains('/webmozart/puli/file1'));
        $this->assertFalse($this->readRepo->contains('/webmozart/puli/file2'));
    }

    public function provideDirectoryGlob()
    {
        return array(
            array('/webmozart/puli'),
            array('/webmozart/pu*'),
        );
    }

    /**
     * @dataProvider provideDirectoryGlob
     */
    public function testRemoveDirectory($glob)
    {
        $this->writeRepo->add('/webmozart/puli/file1', $this->prepareFixtures($this->createFile()));
        $this->writeRepo->add('/webmozart/puli/file2', $this->prepareFixtures($this->createFile()));

        $this->assertTrue($this->readRepo->contains('/webmozart'));
        $this->assertTrue($this->readRepo->contains('/webmozart/puli'));
        $this->assertTrue($this->readRepo->contains('/webmozart/puli/file1'));
        $this->assertTrue($this->readRepo->contains('/webmozart/puli/file2'));

        $this->assertSame(3, $this->writeRepo->remove($glob));

        $this->readRepo = $this->createReadRepository($this->writeRepo);

        $this->assertTrue($this->readRepo->contains('/webmozart'));
        $this->assertFalse($this->readRepo->contains('/webmozart/puli'));
        $this->assertFalse($this->readRepo->contains('/webmozart/puli/file1'));
        $this->assertFalse($this->readRepo->contains('/webmozart/puli/file2'));
    }

    public function testRemoveDot()
    {
        $this->writeRepo->add('/webmozart/puli/file1', $this->prepareFixtures($this->createFile()));
        $this->writeRepo->add('/webmozart/puli/file2', $this->prepareFixtures($this->createFile()));

        $this->assertTrue($this->readRepo->contains('/webmozart'));
        $this->assertTrue($this->readRepo->contains('/webmozart/puli'));
        $this->assertTrue($this->readRepo->contains('/webmozart/puli/file1'));
        $this->assertTrue($this->readRepo->contains('/webmozart/puli/file2'));

        $this->writeRepo->remove('/webmozart/puli/.');

        $this->readRepo = $this->createReadRepository($this->writeRepo);

        $this->assertTrue($this->readRepo->contains('/webmozart'));
        $this->assertFalse($this->readRepo->contains('/webmozart/puli'));
        $this->assertFalse($this->readRepo->contains('/webmozart/puli/file1'));
        $this->assertFalse($this->readRepo->contains('/webmozart/puli/file2'));
    }

    public function testRemoveDotDot()
    {
        $this->writeRepo->add('/webmozart/puli/file1', $this->prepareFixtures($this->createFile()));
        $this->writeRepo->add('/webmozart/puli/file2', $this->prepareFixtures($this->createFile()));

        $this->assertTrue($this->readRepo->contains('/'));
        $this->assertTrue($this->readRepo->contains('/webmozart'));
        $this->assertTrue($this->readRepo->contains('/webmozart/puli'));
        $this->assertTrue($this->readRepo->contains('/webmozart/puli/file1'));
        $this->assertTrue($this->readRepo->contains('/webmozart/puli/file2'));

        $this->writeRepo->remove('/webmozart/puli/..');

        $this->readRepo = $this->createReadRepository($this->writeRepo);

        $this->assertTrue($this->readRepo->contains('/'));
        $this->assertFalse($this->readRepo->contains('/webmozart'));
        $this->assertFalse($this->readRepo->contains('/webmozart/puli'));
        $this->assertFalse($this->readRepo->contains('/webmozart/puli/file1'));
        $this->assertFalse($this->readRepo->contains('/webmozart/puli/file2'));
    }

    public function testRemoveDiscardsTrailingSlash()
    {
        $this->writeRepo->add('/webmozart/puli/file1', $this->prepareFixtures($this->createFile()));
        $this->writeRepo->add('/webmozart/puli/file2', $this->prepareFixtures($this->createFile()));

        $this->assertTrue($this->readRepo->contains('/webmozart/puli'));
        $this->assertTrue($this->readRepo->contains('/webmozart/puli/file1'));
        $this->assertTrue($this->readRepo->contains('/webmozart/puli/file2'));

        $this->writeRepo->remove('/webmozart/puli/');

        $this->readRepo = $this->createReadRepository($this->writeRepo);

        $this->assertFalse($this->readRepo->contains('/webmozart/puli'));
        $this->assertFalse($this->readRepo->contains('/webmozart/puli/file1'));
        $this->assertFalse($this->readRepo->contains('/webmozart/puli/file2'));
    }

    /**
     * @expectedException \InvalidArgumentException
     */
    public function testCannotRemoveRoot()
    {
        $this->writeRepo->remove('/');
    }

    /**
     * @expectedException \InvalidArgumentException
     */
    public function testRemoveInterpretsConsecutiveSlashesAsRoot()
    {
        $this->writeRepo->remove('///');
    }

    /**
     * @expectedException \InvalidArgumentException
     */
    public function testRemoveExpectsAbsolutePath()
    {
        $this->writeRepo->remove('webmozart');
    }

    /**
     * @expectedException \InvalidArgumentException
     */
    public function testRemoveExpectsNonEmptyPath()
    {
        $this->writeRepo->remove('');
    }

    /**
     * @expectedException \InvalidArgumentException
     */
    public function testRemoveExpectsStringPath()
    {
        $this->writeRepo->remove(new \stdClass());
    }

    public function testClear()
    {
        $this->writeRepo->add('/webmozart/puli/file1', $this->prepareFixtures($this->createFile()));
        $this->writeRepo->add('/webmozart/puli/file2', $this->prepareFixtures($this->createFile()));

        $this->assertTrue($this->readRepo->contains('/'));
        $this->assertTrue($this->readRepo->contains('/webmozart'));
        $this->assertTrue($this->readRepo->contains('/webmozart/puli'));
        $this->assertTrue($this->readRepo->contains('/webmozart/puli/file1'));
        $this->assertTrue($this->readRepo->contains('/webmozart/puli/file2'));

        $this->assertSame(4, $this->writeRepo->clear());

        $this->readRepo = $this->createReadRepository($this->writeRepo);

        $this->assertTrue($this->readRepo->contains('/'));
        $this->assertFalse($this->readRepo->contains('/webmozart'));
        $this->assertFalse($this->readRepo->contains('/webmozart/puli'));
        $this->assertFalse($this->readRepo->contains('/webmozart/puli/file1'));
        $this->assertFalse($this->readRepo->contains('/webmozart/puli/file2'));
    }

    public function testFileLink()
    {
        $this->writeRepo->add('/webmozart/file', new FileResource(__DIR__.'/Fixtures/dir1/file1'));
        $this->writeRepo->add('/webmozart/link', new LinkResource('/webmozart/file'));

        $link = $this->readRepo->get('/webmozart/link');

        $this->assertInstanceOf('Puli\Repository\Resource\LinkResource', $link);
        $this->assertSame('/webmozart/link', $link->getPath());
        $this->assertSame('/webmozart/file', $link->getTargetPath());
        $this->assertSame($this->readRepo, $link->getRepository());

        $target = $link->getTarget();

        $this->assertInstanceOf('Puli\Repository\Resource\FileResource', $target);
        $this->assertSame('/webmozart/file', $target->getPath());
        $this->assertSame($this->readRepo, $target->getRepository());

        $target = $this->readRepo->get($link->getTargetPath());

        $this->assertInstanceOf('Puli\Repository\Resource\FileResource', $target);
        $this->assertSame('/webmozart/file', $target->getPath());
        $this->assertSame($this->readRepo, $target->getRepository());
    }

    public function testDirectoryLink()
    {
        $this->writeRepo->add('/webmozart/link/foo', new DirectoryResource(__DIR__.'/Fixtures/dir1'));
        $this->writeRepo->add('/webmozart/link/bar', new LinkResource('/webmozart/link/foo'));

        $link = $this->readRepo->get('/webmozart/link/bar');

        $this->assertInstanceOf('Puli\Repository\Resource\LinkResource', $link);
        $this->assertSame('/webmozart/link/bar', $link->getPath());
        $this->assertSame('/webmozart/link/foo', $link->getTargetPath());
        $this->assertSame($this->readRepo, $link->getRepository());

        $target = $this->readRepo->get($link->getTargetPath());

        $this->assertTrue($target instanceof TestDirectory || $target instanceof DirectoryResource);
        $this->assertSame('/webmozart/link/foo', $target->getPath());
        $this->assertSame($this->readRepo, $target->getRepository());
        $this->assertCount(2, $target->listChildren());
    }

    /**
     * @expectedException \Puli\Repository\Api\NoVersionFoundException
     */
    public function testGetVersionsFailsForDeletedResources()
    {
        $this->writeRepo->add('/webmozart/file', $this->prepareFixtures($this->createFile()));
        $this->writeRepo->remove('/webmozart/file');

        // We cannot guarantee that all repository implementations maintain
        // information about deleted resources
        $this->readRepo->getVersions('/webmozart/file');
    }

    /**
     * @expectedException \Puli\Repository\Api\NoVersionFoundException
     */
    public function testGetVersionsFailsForChildrenOfDeletedResources()
    {
        $this->writeRepo->add('/webmozart', $this->prepareFixtures($this->createDirectory()));
        $this->writeRepo->add('/webmozart/file', $this->prepareFixtures($this->createFile()));
        $this->writeRepo->remove('/webmozart');

        $this->readRepo->getVersions('/webmozart/file');
    }

    /**
     * @expectedException \Puli\Repository\Api\NoVersionFoundException
     */
    public function testGetVersionsFailsAfterClearing()
    {
        $this->writeRepo->add('/webmozart/file', $this->prepareFixtures($this->createFile()));
        $this->writeRepo->clear();

        $this->readRepo->getVersions('/webmozart/file');
    }

    public function testGetVersionsSucceedsForRootAfterClearing()
    {
        $this->writeRepo->clear();

        $this->assertCount(1, $this->readRepo->getVersions('/'));
    }

    public function testGetVersionsDoesNotIncludeDeletedResources()
    {
        $this->writeRepo->add('/webmozart/file', $this->prepareFixtures($this->createFile()));
        $this->writeRepo->remove('/webmozart/file');
        $this->writeRepo->add('/webmozart/file', $this->prepareFixtures($this->createFile(null, 'NEW BODY')));

        $versions = $this->readRepo->getVersions('/webmozart/file');

        $this->assertSame(array(0), $versions->getVersions());

        $resource = $versions->getFirst();

        $this->assertInstanceOf('Puli\Repository\Api\Resource\BodyResource', $resource);
        $this->assertSame('/webmozart/file', $resource->getPath());
        $this->assertSame($this->readRepo, $resource->getRepository());
        $this->assertSame('NEW BODY', $resource->getBody());
    }
}
