<?php

/*
 * This file is part of the Puli package.
 *
 * (c) Bernhard Schussek <bschussek@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Webmozart\Puli\Tests\Filesystem;

use Symfony\Component\Filesystem\Filesystem;
use Webmozart\Puli\Filesystem\Dumper\PhpCacheDumper;
use Webmozart\Puli\Filesystem\PhpCacheLocator;
use Webmozart\Puli\Filesystem\Resource\LocalDirectoryResource;
use Webmozart\Puli\Filesystem\Resource\LocalFileResource;
use Webmozart\Puli\Filesystem\Resource\LocalResource;
use Webmozart\Puli\Repository\ResourceRepository;
use Webmozart\Puli\Resource\DirectoryResourceInterface;
use Webmozart\Puli\Resource\FileResourceInterface;
use Webmozart\Puli\Tests\Locator\AbstractResourceLocatorTest;

/**
 * @since  1.0
 * @author Bernhard Schussek <bschussek@gmail.com>
 */
class PhpCacheLocatorTest extends AbstractResourceLocatorTest
{
    private static $tempDir;

    /**
     * @var Filesystem
     */
    private static $filesystem;

    /**
     * @var \Webmozart\Puli\Filesystem\Dumper\PhpCacheDumper
     */
    private $dumper;

    public static function setUpBeforeClass()
    {
        parent::setUpBeforeClass();

        self::$filesystem = new Filesystem();
        self::$tempDir = sys_get_temp_dir().'/puli/PhpCacheLocatorTest';
    }

    public static function tearDownAfterClass()
    {
        parent::tearDownAfterClass();

        if (file_exists(self::$tempDir)) {
            self::$filesystem->remove(self::$tempDir);
        }
    }

    protected function setUp()
    {
        parent::setUp();

        $this->dumper = new PhpCacheDumper();

        self::$filesystem->remove(self::$tempDir);
        mkdir(self::$tempDir, 0777, true);
    }

    protected function createLocator(ResourceRepository $repository)
    {
        $this->dumper->dumpLocator($repository, self::$tempDir);

        return new PhpCacheLocator(self::$tempDir);
    }

    /**
     * @param string $path
     *
     * @return FileResourceInterface
     */
    protected function createFile($path)
    {
        return LocalFileResource::forPath($path, $this->fixturesDir.$path);
    }

    /**
     * @param string $path
     *
     * @return DirectoryResourceInterface
     */
    protected function createDir($path)
    {
        return LocalDirectoryResource::forPath($path, $this->fixturesDir.$path);
    }

    protected function assertResourceEquals($expected, $actual)
    {
        if ($expected instanceof LocalResource) {
            $this->assertInstanceOf(get_class($expected), $actual);
            $this->assertSame($expected->getPath(), $actual->getPath());
            $this->assertSame($expected->getName(), $actual->getName());
            $this->assertSame($expected->getLocalPath(), $actual->getLocalPath());
            $this->assertSame($expected->getAlternativePaths(), $actual->getAlternativePaths());
        } else {
            $this->assertEquals($expected, $actual);
        }
    }
}
