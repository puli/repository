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

use Puli\Repository\OptimizedPathMappingRepository;
use Puli\Repository\PathMappingRepository;
use Symfony\Component\Filesystem\Filesystem;
use Webmozart\KeyValueStore\Api\KeyValueStore;
use Webmozart\KeyValueStore\ArrayStore;

/**
 * @author Bernhard Schussek <bschussek@gmail.com>
 * @author Titouan Galopin <galopintitouan@gmail.com>
 */
abstract class AbstractPathMappingRepositoryTest extends AbstractEditableRepositoryTest
{
    /**
     * @var ArrayStore
     */
    protected $store;

    /**
     * @var OptimizedPathMappingRepository
     */
    protected $repo;

    /**
     * Temporary directory for test filess.
     *
     * @var string
     */
    protected $tempDir;

    /**
     * Counter to avoid collisions during tests on files.
     *
     * @var int
     */
    protected static $createdFiles = 0;

    protected function setUp()
    {
        parent::setUp();

        $this->tempDir = __DIR__.'/Fixtures/tmp';

        $filesystem = new Filesystem();
        $filesystem->mkdir($this->tempDir);

        $this->store = new ArrayStore();
        $this->repo = $this->createBaseDirectoryRepository($this->store, __DIR__.'/Fixtures');
    }

    protected function tearDown()
    {
        parent::tearDown();

        $filesystem = new Filesystem();
        $filesystem->remove($this->tempDir);
    }

    /**
     * @param KeyValueStore $store
     * @param string $baseDirectory
     *
     * @return PathMappingRepository|OptimizedPathMappingRepository
     */
    abstract protected function createBaseDirectoryRepository(KeyValueStore $store, $baseDirectory);
}
