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
use Puli\Repository\Resource\DirectoryResource;
use Puli\Repository\Resource\FileResource;
use Puli\Repository\Tests\Resource\TestDirectory;
use Symfony\Component\Filesystem\Filesystem;
use Webmozart\Glob\Test\TestUtil;
use Webmozart\PathUtil\Path;

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
    protected static $nextFileId = 0;

    /**
     * Counter to avoid collisions during tests on directories.
     *
     * @var int
     */
    protected static $nextDirectoryId = 0;

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

    /**
     * @expectedException \Puli\Repository\Api\UnsupportedLanguageException
     * @expectedExceptionMessage foobar
     */
    public function testContainsFailsIfLanguageNotGlob()
    {
        $this->readRepo->contains('/*', 'foobar');
    }

    /**
     * @expectedException \Puli\Repository\Api\UnsupportedLanguageException
     * @expectedExceptionMessage foobar
     */
    public function testFindFailsIfLanguageNotGlob()
    {
        $this->readRepo->find('/*', 'foobar');
    }

    /**
     * @expectedException \Puli\Repository\Api\UnsupportedLanguageException
     * @expectedExceptionMessage foobar
     */
    public function testRemoveFailsIfLanguageNotGlob()
    {
        $this->writeRepo->remove('/*', 'foobar');
    }

    protected function prepareFixtures(PuliResource $root)
    {
        return $this->copyToFilesystem($root);
    }

    /**
     * @param PuliResource $resource
     * @param string       $parentPath
     *
     * @return DirectoryResource|FileResource
     */
    private function copyToFilesystem($resource, $parentPath = '')
    {
        $filesystem = new Filesystem();

        if ($resource instanceof TestDirectory) {
            $directoryPath = null === $resource->getPath()
                ? $parentPath.'/dir'.(self::$nextDirectoryId++)
                : $parentPath.rtrim($resource->getPath(), '/');

            $filesystem->mkdir($this->tempFixtureDir.$directoryPath);

            foreach ($resource->listChildren() as $child) {
                $this->copyToFilesystem($child, $directoryPath);
            }

            return new DirectoryResource($this->tempFixtureDir.$directoryPath, $resource->getPath());
        }

        $filePath = null === $resource->getPath()
            ? $parentPath.'/file'.(self::$nextFileId++)
            : $parentPath.rtrim($resource->getPath(), '/');

        $filesystem->mkdir(Path::getDirectory($this->tempFixtureDir.$filePath));

        file_put_contents($this->tempFixtureDir.$filePath, $resource->getBody());

        return new FileResource($this->tempFixtureDir.$filePath, $resource->getPath());
    }
}
