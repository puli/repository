<?php

/*
 * This file is part of the Puli package.
 *
 * (c) Bernhard Schussek <bschussek@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Webmozart\Puli\Tests\Locator;

use Webmozart\Puli\Locator\PhpResourceLocator;
use Webmozart\Puli\LocatorDumper\PhpResourceLocatorDumper;

/**
 * @since  1.0
 * @author Bernhard Schussek <bschussek@gmail.com>
 */
class PhpResourceLocatorTest extends AbstractResourceLocatorTest
{
    private static $tempDir;

    /**
     * @var PhpResourceLocatorDumper
     */
    private $dumper;

    public static function setUpBeforeClass()
    {
        parent::setUpBeforeClass();

        self::$tempDir = sys_get_temp_dir().'/PhpResourceLocatorTest';
    }

    public static function tearDownAfterClass()
    {
        parent::tearDownAfterClass();

        if (file_exists(self::$tempDir)) {
            self::rmdir(self::$tempDir);
        }
    }

    protected function setUp()
    {
        parent::setUp();

        $this->dumper = new PhpResourceLocatorDumper();

        self::rmdir(self::$tempDir);
        mkdir(self::$tempDir, 0777, true);
    }

    protected function dumpLocator()
    {
        $this->dumper->dumpLocator($this->repo, self::$tempDir);

        $this->locator = new PhpResourceLocator(self::$tempDir);
    }

    private static function rmdir($dir)
    {
        if (file_exists($dir)) {
            $iterator = new \RecursiveIteratorIterator(
                new \RecursiveDirectoryIterator(
                    $dir,
                    \FilesystemIterator::SKIP_DOTS
                        | \FilesystemIterator::CURRENT_AS_PATHNAME
                        | \FilesystemIterator::UNIX_PATHS
                ),
                \RecursiveIteratorIterator::CHILD_FIRST
            );

            foreach ($iterator as $path) {
                if (is_file($path)) {
                    unlink($path);

                    continue;
                }

                rmdir($path);
            }

            rmdir($dir);
        }
    }
}
