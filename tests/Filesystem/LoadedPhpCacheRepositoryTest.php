<?php

/*
 * This file is part of the puli/repository package.
 *
 * (c) Bernhard Schussek <bschussek@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Puli\Repository\Tests\Filesystem;

use Puli\Repository\Filesystem\PhpCacheRepository;
use Puli\Repository\Filesystem\Resource\LocalDirectoryResource;
use Puli\Repository\Filesystem\Resource\LocalFileResource;
use Puli\Repository\Filesystem\Resource\LocalResource;
use Puli\Repository\Resource\ResourceInterface;
use Puli\Repository\ResourceRepository;
use Puli\Repository\Resource\DirectoryResourceInterface;
use Puli\Repository\Resource\Iterator\RecursiveResourceIterator;
use Puli\Repository\Resource\Iterator\ResourceCollectionIterator;
use Puli\Repository\Tests\AbstractRepositoryTest;
use Symfony\Component\Filesystem\Filesystem;

/**
 * @since  1.0
 * @author Bernhard Schussek <bschussek@gmail.com>
 */
class LoadedPhpCacheRepositoryTest extends AbstractPhpCacheRepositoryTest
{
    protected function loadRepository($cacheRoot)
    {
        $repo = parent::loadRepository($cacheRoot);

        $this->load($repo->get('/'));

        return $repo;
    }

    private function load(DirectoryResourceInterface $resource)
    {
        foreach ($resource->listEntries() as $entry) {
            if ($entry instanceof DirectoryResourceInterface) {
                $this->load($entry);
            }
        }
    }
}
