<?php

/*
 * This file is part of the Puli package.
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
class PhpCacheRepositoryTest extends AbstractRepositoryTest
{
    /**
     * @var Filesystem
     */
    private $filesystem;

    private $root;

    private $cacheRoot;

    private $repoRoot;

    protected function setUp()
    {
        $this->filesystem = new Filesystem();

        while (false === mkdir($root = sys_get_temp_dir().'/puli/PhpCacheRepositoryTest'.rand(10000, 99999), 0777, true)) {}

        mkdir($root.'/repo');
        mkdir($root.'/cache');

        $this->root = $root;
        $this->cacheRoot = $root.'/cache';
        $this->repoRoot = $root.'/repo';

        parent::setUp();
    }

    protected function tearDown()
    {
        parent::tearDown();

        $this->filesystem->remove($this->root);
    }

    protected function createRepository(DirectoryResourceInterface $root, array $tags = array())
    {
        $iterator = new RecursiveResourceIterator(
            new ResourceCollectionIterator($root->listEntries()),
            RecursiveResourceIterator::SELF_FIRST
        );

        foreach ($iterator as $resource) {
            $localPath = $this->repoRoot.$resource->getPath();
            if ($resource instanceof DirectoryResourceInterface) {
                $this->filesystem->mkdir($localPath);
            } else {
                file_put_contents($localPath, $resource->getContents());
            }
        }

        $pathsByTag = array();

        foreach ($tags as $path => $tag) {
            foreach ((array)$tag as $_tag) {
                if (!isset($pathsByTag[$_tag])) {
                    $pathsByTag[$_tag] = array();
                }

                $pathsByTag[$_tag][] = $path;
            }
        }

        $repo = new ResourceRepository();
        $repo->add('/', new LocalDirectoryResource($this->repoRoot));

        foreach ($tags as $path => $tag) {
            $repo->tag($path, $tag);
        }

        PhpCacheRepository::dumpRepository($repo, $this->cacheRoot);

        return new PhpCacheRepository($this->cacheRoot);
    }

    protected function assertSameResource($expected, $actual)
    {
        if ($expected instanceof LocalResource) {
            $this->assertInstanceOf(get_class($expected), $actual);
            /** @var LocalResource $actual */
            $this->assertSame($expected->getPath(), $actual->getPath());
            $this->assertSame($expected->getName(), $actual->getName());
            $this->assertSame($expected->getLocalPath(), $actual->getLocalPath());
            $this->assertSame($expected->getAllLocalPaths(), $actual->getAllLocalPaths());
        } else {
            $this->assertSame($expected, $actual);
        }
    }

    /**
     * @expectedException \RuntimeException
     */
    public function testFailIfInvalidDump()
    {
        PhpCacheRepository::dumpRepository(new ResourceRepository(), $this->cacheRoot);

        $files = glob($this->cacheRoot.'/*');
        unlink($files[1]);

        new PhpCacheRepository($this->cacheRoot);
    }

    public function testGetFile()
    {
        touch($this->repoRoot.'/file');

        $repo = new ResourceRepository();
        $repo->add('/webmozart/puli/file', new LocalFileResource($this->repoRoot.'/file'));

        PhpCacheRepository::dumpRepository($repo, $this->cacheRoot);

        $repo = new PhpCacheRepository($this->cacheRoot);
        $file = $repo->get('/webmozart/puli/file');

        $this->assertInstanceOf('Puli\Repository\Filesystem\Resource\LocalFileResource', $file);
        $this->assertSame('/webmozart/puli/file', $file->getPath());
        $this->assertSame($this->repoRoot.'/file', $file->getLocalPath());
        $this->assertSame(array($this->repoRoot.'/file'), $file->getAllLocalPaths());
    }

    public function testGetLoadedFile()
    {
        touch($this->repoRoot.'/file');

        $repo = new ResourceRepository();
        $repo->add('/webmozart/puli/file', new LocalFileResource($this->repoRoot.'/file'));

        PhpCacheRepository::dumpRepository($repo, $this->cacheRoot);

        $repo = new PhpCacheRepository($this->cacheRoot);
        // load first
        $repo->get('/webmozart/puli/file');
        $file = $repo->get('/webmozart/puli/file');

        $this->assertInstanceOf('Puli\Repository\Filesystem\Resource\LocalFileResource', $file);
        $this->assertSame('/webmozart/puli/file', $file->getPath());
        $this->assertSame($this->repoRoot.'/file', $file->getLocalPath());
        $this->assertSame(array($this->repoRoot.'/file'), $file->getAllLocalPaths());
    }

    public function testGetDirectory()
    {
        mkdir($this->repoRoot.'/dir');

        $repo = new ResourceRepository();
        $repo->add('/webmozart/puli/dir', new LocalDirectoryResource($this->repoRoot.'/dir'));

        PhpCacheRepository::dumpRepository($repo, $this->cacheRoot);

        $repo = new PhpCacheRepository($this->cacheRoot);
        $dir = $repo->get('/webmozart/puli/dir');

        $this->assertInstanceOf('Puli\Repository\Filesystem\Resource\LocalDirectoryResource', $dir);
        $this->assertSame('/webmozart/puli/dir', $dir->getPath());
        $this->assertSame($this->repoRoot.'/dir', $dir->getLocalPath());
        $this->assertSame(array($this->repoRoot.'/dir'), $dir->getAllLocalPaths());
    }

    public function testGetLoadedDirectory()
    {
        mkdir($this->repoRoot.'/dir');

        $repo = new ResourceRepository();
        $repo->add('/webmozart/puli/dir', new LocalDirectoryResource($this->repoRoot.'/dir'));

        PhpCacheRepository::dumpRepository($repo, $this->cacheRoot);

        $repo = new PhpCacheRepository($this->cacheRoot);
        // load first
        $repo->get('/webmozart/puli/dir');
        $dir = $repo->get('/webmozart/puli/dir');

        $this->assertInstanceOf('Puli\Repository\Filesystem\Resource\LocalDirectoryResource', $dir);
        $this->assertSame('/webmozart/puli/dir', $dir->getPath());
        $this->assertSame($this->repoRoot.'/dir', $dir->getLocalPath());
        $this->assertSame(array($this->repoRoot.'/dir'), $dir->getAllLocalPaths());
    }

    public function testGetOverriddenFile()
    {
        touch($this->repoRoot.'/file1');
        touch($this->repoRoot.'/file2');

        $repo = new ResourceRepository();
        $repo->add('/webmozart/puli/file', new LocalFileResource($this->repoRoot.'/file1'));
        $repo->add('/webmozart/puli/file', new LocalFileResource($this->repoRoot.'/file2'));

        PhpCacheRepository::dumpRepository($repo, $this->cacheRoot);

        $repo = new PhpCacheRepository($this->cacheRoot);
        $file = $repo->get('/webmozart/puli/file');

        $this->assertInstanceOf('Puli\Repository\Filesystem\Resource\LocalFileResource', $file);
        $this->assertSame('/webmozart/puli/file', $file->getPath());
        $this->assertSame($this->repoRoot.'/file2', $file->getLocalPath());
        $this->assertSame(array($this->repoRoot.'/file1', $this->repoRoot.'/file2'), $file->getAllLocalPaths());
    }

    public function testGetOverriddenDirectory()
    {
        mkdir($this->repoRoot.'/dir1');
        touch($this->repoRoot.'/dir1/foo');
        mkdir($this->repoRoot.'/dir2');
        touch($this->repoRoot.'/dir2/foo');
        touch($this->repoRoot.'/dir2/bar');

        $repo = new ResourceRepository();
        $repo->add('/webmozart/puli/dir', new LocalDirectoryResource($this->repoRoot.'/dir1'));
        $repo->add('/webmozart/puli/dir', new LocalDirectoryResource($this->repoRoot.'/dir2'));

        PhpCacheRepository::dumpRepository($repo, $this->cacheRoot);

        $repo = new PhpCacheRepository($this->cacheRoot);
        $dir = $repo->get('/webmozart/puli/dir');
        $entries = $dir->listEntries();
        $entries = $dir->listEntries();

        $this->assertInstanceOf('Puli\Repository\Filesystem\Resource\LocalDirectoryResource', $dir);
        $this->assertCount(2, $entries);
        $this->assertSame('/webmozart/puli/dir', $dir->getPath());
        $this->assertSame($this->repoRoot.'/dir2', $dir->getLocalPath());
        $this->assertSame(array($this->repoRoot.'/dir1', $this->repoRoot.'/dir2'), $dir->getAllLocalPaths());

        // sorted
        $this->assertSame(array('bar', 'foo'), array_keys($entries->toArray()));

        $this->assertInstanceOf('Puli\Repository\Filesystem\Resource\LocalFileResource', $entries['bar']);
        $this->assertSame('/webmozart/puli/dir/bar', $entries['bar']->getPath());
        $this->assertSame($this->repoRoot.'/dir2/bar', $entries['bar']->getLocalPath());
        $this->assertSame(array($this->repoRoot.'/dir2/bar'), $entries['bar']->getAllLocalPaths());

        $this->assertInstanceOf('Puli\Repository\Filesystem\Resource\LocalFileResource', $entries['foo']);
        $this->assertSame('/webmozart/puli/dir/foo', $entries['foo']->getPath());
        $this->assertSame($this->repoRoot.'/dir2/foo', $entries['foo']->getLocalPath());
        $this->assertSame(array($this->repoRoot.'/dir1/foo', $this->repoRoot.'/dir2/foo'), $entries['foo']->getAllLocalPaths());
    }

    public function testFindLoadedFile()
    {
        touch($this->repoRoot.'/file');

        $repo = new ResourceRepository();
        $repo->add('/webmozart/puli/file', new LocalFileResource($this->repoRoot.'/file'));

        PhpCacheRepository::dumpRepository($repo, $this->cacheRoot);

        $repo = new PhpCacheRepository($this->cacheRoot);
        // load first
        $repo->get('/webmozart/puli/file');
        $resources = $repo->find('/webmozart/puli/file');

        $this->assertInstanceOf('Puli\Repository\Filesystem\Resource\LocalResourceCollection', $resources);
        $this->assertCount(1, $resources);

        $this->assertInstanceOf('Puli\Repository\Filesystem\Resource\LocalFileResource', $resources[0]);
        $this->assertSame('/webmozart/puli/file', $resources[0]->getPath());
        $this->assertSame($this->repoRoot.'/file', $resources[0]->getLocalPath());
        $this->assertSame(array($this->repoRoot.'/file'), $resources[0]->getAllLocalPaths());
    }
}
