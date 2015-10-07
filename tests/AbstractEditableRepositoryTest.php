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
        $this->writeRepo->add('/webmozart/puli', $this->buildStructure($this->createDirectory()));
        $this->writeRepo->add('/webmozart/puli/file', $this->buildStructure($this->createFile()));

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

    public function testAddMergesResourceChildren()
    {
        $this->writeRepo->add('/webmozart/puli', $this->buildStructure($this->createDirectory('/foo', array(
            $this->createFile('/file1', 'original 1'),
            $this->createFile('/file2', 'original 2'),
        ))));

        $this->writeRepo->add('/webmozart/puli', $this->buildStructure($this->createDirectory('/bar', array(
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
        $this->writeRepo->add('/webmozart/puli/file/.', $this->buildStructure($this->createFile()));

        $file = $this->readRepo->get('/webmozart/puli/file');

        $this->assertInstanceOf('Puli\Repository\Api\Resource\BodyResource', $file);
        $this->assertSame('/webmozart/puli/file', $file->getPath());
    }

    public function testAddDotDot()
    {
        $this->writeRepo->add('/webmozart/puli/file/..', $this->buildStructure($this->createFile()));

        $file = $this->readRepo->get('/webmozart/puli');

        $this->assertInstanceOf('Puli\Repository\Api\Resource\BodyResource', $file);
        $this->assertSame('/webmozart/puli', $file->getPath());
    }

    public function testAddTrimsTrailingSlash()
    {
        $this->writeRepo->add('/webmozart/puli/file/', $this->buildStructure($this->createFile()));

        $file = $this->readRepo->get('/webmozart/puli/file');

        $this->assertInstanceOf('Puli\Repository\Api\Resource\BodyResource', $file);
        $this->assertSame('/webmozart/puli/file', $file->getPath());
    }

    public function testAddCollection()
    {
        $this->writeRepo->add('/webmozart/puli', new ArrayResourceCollection(array(
            $this->buildStructure($this->createFile('/file1')),
            $this->buildStructure($this->createFile('/file2')),
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
        $this->writeRepo->add('/', $this->buildStructure($this->createDirectory('/', array(
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
        $this->writeRepo->add('webmozart', $this->buildStructure($this->createDirectory()));
    }

    /**
     * @expectedException \InvalidArgumentException
     */
    public function testAddExpectsNonEmptyPath()
    {
        $this->writeRepo->add('', $this->buildStructure($this->createDirectory()));
    }

    /**
     * @expectedException \InvalidArgumentException
     */
    public function testAddExpectsStringPath()
    {
        $this->writeRepo->add(new \stdClass(), $this->buildStructure($this->createDirectory()));
    }

    /**
     * @expectedException \Puli\Repository\Api\UnsupportedResourceException
     */
    public function testAddExpectsResource()
    {
        $this->writeRepo->add('/webmozart', new \stdClass());
    }

    public function testRemoveFile()
    {
        $this->writeRepo->add('/webmozart/puli/file1', $this->buildStructure($this->createFile()));
        $this->writeRepo->add('/webmozart/puli/file2', $this->buildStructure($this->createFile()));

        $this->assertTrue($this->readRepo->contains('/webmozart'));
        $this->assertTrue($this->readRepo->contains('/webmozart/puli'));
        $this->assertTrue($this->readRepo->contains('/webmozart/puli/file1'));
        $this->assertTrue($this->readRepo->contains('/webmozart/puli/file2'));

        $this->assertSame(1, $this->writeRepo->remove('/webmozart/puli/file1'));

        $this->assertTrue($this->readRepo->contains('/webmozart'));
        $this->assertTrue($this->readRepo->contains('/webmozart/puli'));
        $this->assertFalse($this->readRepo->contains('/webmozart/puli/file1'));
        $this->assertTrue($this->readRepo->contains('/webmozart/puli/file2'));
    }

    public function testRemoveMany()
    {
        $this->writeRepo->add('/webmozart/puli/file1', $this->buildStructure($this->createFile()));
        $this->writeRepo->add('/webmozart/puli/file2', $this->buildStructure($this->createFile()));

        $this->assertTrue($this->readRepo->contains('/webmozart/puli'));
        $this->assertTrue($this->readRepo->contains('/webmozart/puli/file1'));
        $this->assertTrue($this->readRepo->contains('/webmozart/puli/file2'));

        $this->assertSame(2, $this->writeRepo->remove('/webmozart/puli/file*'));

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
        $this->writeRepo->add('/webmozart/puli/file1', $this->buildStructure($this->createFile()));
        $this->writeRepo->add('/webmozart/puli/file2', $this->buildStructure($this->createFile()));

        $this->assertTrue($this->readRepo->contains('/webmozart'));
        $this->assertTrue($this->readRepo->contains('/webmozart/puli'));
        $this->assertTrue($this->readRepo->contains('/webmozart/puli/file1'));
        $this->assertTrue($this->readRepo->contains('/webmozart/puli/file2'));

        $this->assertSame(3, $this->writeRepo->remove($glob));

        $this->assertTrue($this->readRepo->contains('/webmozart'));
        $this->assertFalse($this->readRepo->contains('/webmozart/puli'));
        $this->assertFalse($this->readRepo->contains('/webmozart/puli/file1'));
        $this->assertFalse($this->readRepo->contains('/webmozart/puli/file2'));
    }

    public function testRemoveDot()
    {
        $this->writeRepo->add('/webmozart/puli/file1', $this->buildStructure($this->createFile()));
        $this->writeRepo->add('/webmozart/puli/file2', $this->buildStructure($this->createFile()));

        $this->assertTrue($this->readRepo->contains('/webmozart'));
        $this->assertTrue($this->readRepo->contains('/webmozart/puli'));
        $this->assertTrue($this->readRepo->contains('/webmozart/puli/file1'));
        $this->assertTrue($this->readRepo->contains('/webmozart/puli/file2'));

        $this->writeRepo->remove('/webmozart/puli/.');

        $this->assertTrue($this->readRepo->contains('/webmozart'));
        $this->assertFalse($this->readRepo->contains('/webmozart/puli'));
        $this->assertFalse($this->readRepo->contains('/webmozart/puli/file1'));
        $this->assertFalse($this->readRepo->contains('/webmozart/puli/file2'));
    }

    public function testRemoveDotDot()
    {
        $this->writeRepo->add('/webmozart/puli/file1', $this->buildStructure($this->createFile()));
        $this->writeRepo->add('/webmozart/puli/file2', $this->buildStructure($this->createFile()));

        $this->assertTrue($this->readRepo->contains('/'));
        $this->assertTrue($this->readRepo->contains('/webmozart'));
        $this->assertTrue($this->readRepo->contains('/webmozart/puli'));
        $this->assertTrue($this->readRepo->contains('/webmozart/puli/file1'));
        $this->assertTrue($this->readRepo->contains('/webmozart/puli/file2'));

        $this->writeRepo->remove('/webmozart/puli/..');

        $this->assertTrue($this->readRepo->contains('/'));
        $this->assertFalse($this->readRepo->contains('/webmozart'));
        $this->assertFalse($this->readRepo->contains('/webmozart/puli'));
        $this->assertFalse($this->readRepo->contains('/webmozart/puli/file1'));
        $this->assertFalse($this->readRepo->contains('/webmozart/puli/file2'));
    }

    public function testRemoveDiscardsTrailingSlash()
    {
        $this->writeRepo->add('/webmozart/puli/file1', $this->buildStructure($this->createFile()));
        $this->writeRepo->add('/webmozart/puli/file2', $this->buildStructure($this->createFile()));

        $this->assertTrue($this->readRepo->contains('/webmozart/puli'));
        $this->assertTrue($this->readRepo->contains('/webmozart/puli/file1'));
        $this->assertTrue($this->readRepo->contains('/webmozart/puli/file2'));

        $this->writeRepo->remove('/webmozart/puli/');

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
        $this->writeRepo->add('/webmozart/puli/file1', $this->buildStructure($this->createFile()));
        $this->writeRepo->add('/webmozart/puli/file2', $this->buildStructure($this->createFile()));

        $this->assertTrue($this->readRepo->contains('/'));
        $this->assertTrue($this->readRepo->contains('/webmozart'));
        $this->assertTrue($this->readRepo->contains('/webmozart/puli'));
        $this->assertTrue($this->readRepo->contains('/webmozart/puli/file1'));
        $this->assertTrue($this->readRepo->contains('/webmozart/puli/file2'));

        $this->assertSame(4, $this->writeRepo->clear());

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
}
