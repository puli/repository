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
use Puli\Repository\Api\Resource\Resource;
use Puli\Repository\FilesystemRepository;
use Puli\Repository\Resource\LinkResource;
use Symfony\Component\Filesystem\Filesystem;
use Webmozart\Glob\Test\TestUtil;

/**
 * @author Bernhard Schussek <bschussek@gmail.com>
 */
class FilesystemRepositoryLoadedTest extends AbstractEditableRepositoryTest
{
    private $tempDir;

    protected function setUp()
    {
        $this->tempDir = TestUtil::makeTempDir('puli-repository', __CLASS__);

        parent::setUp();
    }

    protected function tearDown()
    {
        parent::tearDown();

        $filesystem = new Filesystem();
        $filesystem->remove($this->tempDir);
    }

    protected function createPrefilledRepository(Resource $root)
    {
        $repo = new FilesystemRepository($this->tempDir, false);
        $repo->add('/', $root);

        return $repo;
    }

    protected function createWriteRepository()
    {
        return new FilesystemRepository($this->tempDir, false);
    }

    protected function createReadRepository(EditableRepository $writeRepo)
    {
        return $writeRepo;
    }

    /**
     * @expectedException \Puli\Repository\Api\UnsupportedResourceException
     */
    public function testFileLink()
    {
        $this->writeRepo->add('/webmozart/link', new LinkResource('/webmozart/puli/file'));
    }

    /**
     * @expectedException \Puli\Repository\Api\UnsupportedResourceException
     */
    public function testDirectoryLink()
    {
        $this->writeRepo->add('/webmozart/link', new LinkResource('/webmozart/puli/file'));
    }
}
