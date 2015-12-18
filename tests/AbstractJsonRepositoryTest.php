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

use Puli\Repository\Api\Resource\PuliResource;
use Puli\Repository\OptimizedJsonRepository;
use Puli\Repository\JsonRepository;
use Puli\Repository\Resource\DirectoryResource;
use Puli\Repository\Resource\FileResource;
use Puli\Repository\Tests\Resource\TestFilesystemDirectory;
use Puli\Repository\Tests\Resource\TestFilesystemFile;
use Symfony\Component\Filesystem\Filesystem;
use Webmozart\Glob\Test\TestUtil;

/**
 * @author Bernhard Schussek <bschussek@gmail.com>
 * @author Titouan Galopin <galopintitouan@gmail.com>
 */
abstract class AbstractJsonRepositoryTest extends AbstractEditableRepositoryTest
{
    /**
     * @var string
     */
    protected $path;

    /**
     * @var string
     */
    protected $tempDir;

    /**
     * Contains a copy of the static fixtures.
     *
     * @var string
     */
    protected $fixtureDir;

    /**
     * Contains dynamically created fixtures.
     *
     * @var string
     */
    protected $tempFixtureDir;

    /**
     * Counter to avoid collisions during tests on files.
     *
     * @var int
     */
    protected static $createdFiles = 0;

    /**
     * Counter to avoid collisions during tests on directories.
     *
     * @var int
     */
    protected static $createdDirectories = 0;

    protected function setUp()
    {
        $this->tempDir = TestUtil::makeTempDir('puli-respository', __CLASS__);
        $this->fixtureDir = $this->tempDir.'/fixtures';
        $this->tempFixtureDir = $this->tempDir.'/temp-fixtures';
        $this->path = $this->tempDir.'/puli.json';

        $filesystem = new Filesystem();
        $filesystem->mkdir($this->tempFixtureDir);
        $filesystem->mirror(__DIR__.'/Fixtures', $this->fixtureDir);

        parent::setUp();
    }

    protected function tearDown()
    {
        parent::tearDown();

        $filesystem = new Filesystem();
        $filesystem->remove($this->tempDir);
    }

    protected function createFile($path = null, $body = TestFilesystemFile::BODY)
    {
        return new TestFilesystemFile($path, $body);
    }

    protected function createDirectory($path = null, array $children = array())
    {
        return new TestFilesystemDirectory($path, $children);
    }

    protected function prepareFixtures(PuliResource $root)
    {
        return $this->recursivePrepareFixtures($root);
    }

    /**
     * @param TestFilesystemFile|TestFilesystemDirectory $resource
     * @param string                                     $parentPath
     *
     * @return DirectoryResource|FileResource
     */
    private function recursivePrepareFixtures($resource, $parentPath = '')
    {
        if ($resource instanceof TestFilesystemDirectory) {
            if ($resource->getPath() !== null) {
                $dirname = rtrim($parentPath.$resource->getPath(), '/');
            } else {
                $dirname = $parentPath.'/dir'.self::$createdDirectories;
                ++self::$createdDirectories;
            }

            if (!is_dir($this->tempFixtureDir.$dirname)) {
                mkdir($this->tempFixtureDir.$dirname, 0777, true);
            }

            foreach ($resource->listChildren() as $child) {
                $this->recursivePrepareFixtures($child, $dirname);
            }

            return new DirectoryResource($this->tempFixtureDir.$dirname, $resource->getPath());
        } else {
            if ($resource->getPath() !== null) {
                $filename = rtrim($parentPath.$resource->getPath(), '/');
            } else {
                $filename = $parentPath.'/file'.self::$createdFiles;
                ++self::$createdFiles;
            }

            $dirname = dirname($this->tempFixtureDir.$filename);

            if (!is_dir($dirname)) {
                mkdir($dirname, 0777, true);
            }

            file_put_contents($this->tempFixtureDir.$filename, $resource->getBody());

            return new FileResource($this->tempFixtureDir.$filename, $resource->getPath());
        }
    }
}
