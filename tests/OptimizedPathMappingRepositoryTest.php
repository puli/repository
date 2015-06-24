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
use Puli\Repository\OptimizedPathMappingRepository;
use Puli\Repository\Tests\Resource\TestFilesystemDirectory;
use Puli\Repository\Tests\Resource\TestFilesystemFile;
use Webmozart\KeyValueStore\ArrayStore;

/**
 * @since  1.0
 * @author Bernhard Schussek <bschussek@gmail.com>
 * @author Titouan Galopin <galopintitouan@gmail.com>
 */
class OptimizedPathMappingRepositoryTest extends AbstractEditableRepositoryTest
{
    /**
     * @var ArrayStore
     */
    protected $store;

    /**
     * @var OptimizedPathMappingRepository
     */
    protected $repo;

    protected function setUp()
    {
        parent::setUp();

        $this->store = new ArrayStore();
        $this->repo = new OptimizedPathMappingRepository($this->store);
    }

    protected function createPrefilledRepository(Resource $root)
    {
        $repo = new OptimizedPathMappingRepository(new ArrayStore());
        $repo->add('/', $root);

        return $repo;
    }

    protected function createWriteRepository()
    {
        return new OptimizedPathMappingRepository(new ArrayStore());
    }

    protected function createReadRepository(EditableRepository $writeRepo)
    {
        return $writeRepo;
    }

    /**
     * @param string $path
     * @param string $body
     *
     * @return TestFilesystemFile
     */
    protected function createFile($path = null, $body = TestFilesystemFile::BODY)
    {
        return new TestFilesystemFile($path, $body);
    }

    /**
     * @param string $path
     * @param array $children
     *
     * @return TestFilesystemDirectory
     */
    protected function createDirectory($path = null, array $children = array())
    {
        return new TestFilesystemDirectory($path, $children);
    }
}
