<?php

/*
 * This file is part of the Puli package.
 *
 * (c) Bernhard Schussek <bschussek@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Webmozart\Puli\Tests\Path;

use Webmozart\Puli\Path\Path;

/**
 * @since  1.0
 * @author Bernhard Schussek <bschussek@gmail.com>
 */
class PathTest extends \PHPUnit_Framework_TestCase
{
    public function provideCanonicalizationTests()
    {
        return array(
            // relative paths (forward slash)
            array('css/./style.css', 'css/style.css'),
            array('css/../style.css', 'style.css'),
            array('css/./../style.css', 'style.css'),
            array('css/.././style.css', 'style.css'),
            array('css/../../style.css', '../style.css'),
            array('./css/style.css', 'css/style.css'),
            array('../css/style.css', '../css/style.css'),
            array('./../css/style.css', '../css/style.css'),
            array('.././css/style.css', '../css/style.css'),
            array('../../css/style.css', '../../css/style.css'),
            array('', ''),
            array(null, ''),
            array('.', ''),
            array('..', '..'),
            array('./..', '..'),
            array('../.', '..'),
            array('../..', '../..'),

            // relative paths (backslash)
            array('css\\.\\style.css', 'css/style.css'),
            array('css\\..\\style.css', 'style.css'),
            array('css\\.\\..\\style.css', 'style.css'),
            array('css\\..\\.\\style.css', 'style.css'),
            array('css\\..\\..\\style.css', '../style.css'),
            array('.\\css\\style.css', 'css/style.css'),
            array('..\\css\\style.css', '../css/style.css'),
            array('.\\..\\css\\style.css', '../css/style.css'),
            array('..\\.\\css\\style.css', '../css/style.css'),
            array('..\\..\\css\\style.css', '../../css/style.css'),

            // absolute paths (forward slash, UNIX)
            array('/css/style.css', '/css/style.css'),
            array('/css/./style.css', '/css/style.css'),
            array('/css/../style.css', '/style.css'),
            array('/css/./../style.css', '/style.css'),
            array('/css/.././style.css', '/style.css'),
            array('/./css/style.css', '/css/style.css'),
            array('/../css/style.css', '/css/style.css'),
            array('/./../css/style.css', '/css/style.css'),
            array('/.././css/style.css', '/css/style.css'),
            array('/../../css/style.css', '/css/style.css'),

            // absolute paths (backslash, UNIX)
            array('\\css\\style.css', '/css/style.css'),
            array('\\css\\.\\style.css', '/css/style.css'),
            array('\\css\\..\\style.css', '/style.css'),
            array('\\css\\.\\..\\style.css', '/style.css'),
            array('\\css\\..\\.\\style.css', '/style.css'),
            array('\\.\\css\\style.css', '/css/style.css'),
            array('\\..\\css\\style.css', '/css/style.css'),
            array('\\.\\..\\css\\style.css', '/css/style.css'),
            array('\\..\\.\\css\\style.css', '/css/style.css'),
            array('\\..\\..\\css\\style.css', '/css/style.css'),

            // absolute paths (forward slash, Windows)
            array('C:/css/style.css', 'C:/css/style.css'),
            array('C:/css/./style.css', 'C:/css/style.css'),
            array('C:/css/../style.css', 'C:/style.css'),
            array('C:/css/./../style.css', 'C:/style.css'),
            array('C:/css/.././style.css', 'C:/style.css'),
            array('C:/./css/style.css', 'C:/css/style.css'),
            array('C:/../css/style.css', 'C:/css/style.css'),
            array('C:/./../css/style.css', 'C:/css/style.css'),
            array('C:/.././css/style.css', 'C:/css/style.css'),
            array('C:/../../css/style.css', 'C:/css/style.css'),

            // absolute paths (backslash, Windows)
            array('C:\\css\\style.css', 'C:/css/style.css'),
            array('C:\\css\\.\\style.css', 'C:/css/style.css'),
            array('C:\\css\\..\\style.css', 'C:/style.css'),
            array('C:\\css\\.\\..\\style.css', 'C:/style.css'),
            array('C:\\css\\..\\.\\style.css', 'C:/style.css'),
            array('C:\\.\\css\\style.css', 'C:/css/style.css'),
            array('C:\\..\\css\\style.css', 'C:/css/style.css'),
            array('C:\\.\\..\\css\\style.css', 'C:/css/style.css'),
            array('C:\\..\\.\\css\\style.css', 'C:/css/style.css'),
            array('C:\\..\\..\\css\\style.css', 'C:/css/style.css'),

            // Windows special case
            array('C:', 'C:/'),

            // Don't change malformed path
            array('C:css/style.css', 'C:css/style.css'),
        );
    }

    /**
     * @dataProvider provideCanonicalizationTests
     */
    public function testCanonicalize($path, $canonicalized)
    {
        $this->assertSame($canonicalized, Path::canonicalize($path));
    }

    public function provideGetDirectoryTests()
    {
        return array(
            array('/webmozart/puli/style.css', '/webmozart/puli'),
            array('/webmozart/puli', '/webmozart'),
            array('/webmozart', '/'),
            array('/', '/'),
            array('', ''),
            array(null, ''),

            array('\\webmozart\\puli\\style.css', '/webmozart/puli'),
            array('\\webmozart\\puli', '/webmozart'),
            array('\\webmozart', '/'),
            array('\\', '/'),

            array('C:/webmozart/puli/style.css', 'C:/webmozart/puli'),
            array('C:/webmozart/puli', 'C:/webmozart'),
            array('C:/webmozart', 'C:/'),
            array('C:/', 'C:/'),
            array('C:', 'C:/'),

            array('C:\\webmozart\\puli\\style.css', 'C:/webmozart/puli'),
            array('C:\\webmozart\\puli', 'C:/webmozart'),
            array('C:\\webmozart', 'C:/'),
            array('C:\\', 'C:/'),

            array('webmozart/puli/style.css', 'webmozart/puli'),
            array('webmozart/puli', 'webmozart'),
            array('webmozart', ''),

            array('webmozart\\puli\\style.css', 'webmozart/puli'),
            array('webmozart\\puli', 'webmozart'),
            array('webmozart', ''),

            array('/webmozart/./puli/style.css', '/webmozart/puli'),
            array('/webmozart/../puli/style.css', '/puli'),
            array('/webmozart/./../puli/style.css', '/puli'),
            array('/webmozart/.././puli/style.css', '/puli'),
            array('/webmozart/../../puli/style.css', '/puli'),
            array('/.', '/'),
            array('/..', '/'),

            array('C:webmozart', ''),
        );
    }

    /**
     * @dataProvider provideGetDirectoryTests
     */
    public function testGetDirectory($path, $directory)
    {
        $this->assertSame($directory, Path::getDirectory($path));
    }

    public function provideIsAbsolutePathTests()
    {
        return array(
            array('/css/style.css', true),
            array('/', true),
            array('css/style.css', false),
            array('', false),
            array(null, false),

            array('\\css\\style.css', true),
            array('\\', true),
            array('css\\style.css', false),

            array('C:/css/style.css', true),
            array('D:/', true),

            array('E:\\css\\style.css', true),
            array('F:\\', true),

            // Windows special case
            array('C:', true),

            // Not considered absolute
            array('C:css/style.css', false),
        );
    }

    /**
     * @dataProvider provideIsAbsolutePathTests
     */
    public function testIsAbsolute($path, $isAbsolute)
    {
        $this->assertSame($isAbsolute, Path::isAbsolute($path));
    }

    /**
     * @dataProvider provideIsAbsolutePathTests
     */
    public function testIsRelative($path, $isAbsolute)
    {
        $this->assertSame(!$isAbsolute, Path::isRelative($path));
    }

    public function provideGetRootTests()
    {
        return array(
            array('/css/style.css', '/'),
            array('/', '/'),
            array('css/style.css', ''),
            array('', ''),
            array(null, ''),

            array('\\css\\style.css', '/'),
            array('\\', '/'),
            array('css\\style.css', ''),

            array('C:/css/style.css', 'C:/'),
            array('C:/', 'C:/'),
            array('C:', 'C:/'),

            array('D:\\css\\style.css', 'D:/'),
            array('D:\\', 'D:/'),
        );
    }

    /**
     * @dataProvider provideGetRootTests
     */
    public function testGetRoot($path, $root)
    {
        $this->assertSame($root, Path::getRoot($path));
    }

    public function providePathTests()
    {
        return array(
            // relative to path
            array('/webmozart/puli', 'css/style.css', '/webmozart/puli/css/style.css'),
            array('/webmozart/puli', '../css/style.css', '/webmozart/css/style.css'),
            array('/webmozart/puli', '../../css/style.css', '/css/style.css'),

            // relative to root
            array('/', 'css/style.css', '/css/style.css'),
            array('C:', 'css/style.css', 'C:/css/style.css'),
            array('C:/', 'css/style.css', 'C:/css/style.css'),
        );
    }

    public function provideMakeAbsoluteTests()
    {
        return array_merge($this->providePathTests(), array(
            // relative to empty
            array('', 'css/style.css', '/css/style.css'),
            array(null, 'css/style.css', '/css/style.css'),

            array('', 'css\\style.css', '/css/style.css'),
            array(null, 'css\\style.css', '/css/style.css'),

            // collapse dots
            array('/webmozart/puli', 'css/./style.css', '/webmozart/puli/css/style.css'),
            array('/webmozart/puli', 'css/../style.css', '/webmozart/puli/style.css'),
            array('/webmozart/puli', 'css/./../style.css', '/webmozart/puli/style.css'),
            array('/webmozart/puli', 'css/.././style.css', '/webmozart/puli/style.css'),
            array('/webmozart/puli', './css/style.css', '/webmozart/puli/css/style.css'),

            array('\\webmozart\\puli', 'css\\.\\style.css', '/webmozart/puli/css/style.css'),
            array('\\webmozart\\puli', 'css\\..\\style.css', '/webmozart/puli/style.css'),
            array('\\webmozart\\puli', 'css\\.\\..\\style.css', '/webmozart/puli/style.css'),
            array('\\webmozart\\puli', 'css\\..\\.\\style.css', '/webmozart/puli/style.css'),
            array('\\webmozart\\puli', '.\\css\\style.css', '/webmozart/puli/css/style.css'),

            // collapse dots on root
            array('/', './css/style.css', '/css/style.css'),
            array('/', '../css/style.css', '/css/style.css'),
            array('/', '../css/./style.css', '/css/style.css'),
            array('/', '../css/../style.css', '/style.css'),
            array('/', '../css/./../style.css', '/style.css'),
            array('/', '../css/.././style.css', '/style.css'),

            array('\\', '.\\css\\style.css', '/css/style.css'),
            array('\\', '..\\css\\style.css', '/css/style.css'),
            array('\\', '..\\css\\.\\style.css', '/css/style.css'),
            array('\\', '..\\css\\..\\style.css', '/style.css'),
            array('\\', '..\\css\\.\\..\\style.css', '/style.css'),
            array('\\', '..\\css\\..\\.\\style.css', '/style.css'),

            array('C:/', './css/style.css', 'C:/css/style.css'),
            array('C:/', '../css/style.css', 'C:/css/style.css'),
            array('C:/', '../css/./style.css', 'C:/css/style.css'),
            array('C:/', '../css/../style.css', 'C:/style.css'),
            array('C:/', '../css/./../style.css', 'C:/style.css'),
            array('C:/', '../css/.././style.css', 'C:/style.css'),

            array('C:\\', '.\\css\\style.css', 'C:/css/style.css'),
            array('C:\\', '..\\css\\style.css', 'C:/css/style.css'),
            array('C:\\', '..\\css\\.\\style.css', 'C:/css/style.css'),
            array('C:\\', '..\\css\\..\\style.css', 'C:/style.css'),
            array('C:\\', '..\\css\\.\\..\\style.css', 'C:/style.css'),
            array('C:\\', '..\\css\\..\\.\\style.css', 'C:/style.css'),

            // collapse dots on empty
            array('', './css/style.css', '/css/style.css'),
            array('', '../css/style.css', '/css/style.css'),
            array('', '../css/./style.css', '/css/style.css'),
            array('', '../css/../style.css', '/style.css'),
            array('', '../css/./../style.css', '/style.css'),
            array('', '../css/.././style.css', '/style.css'),

            array('', '.\\css\\style.css', '/css/style.css'),
            array('', '..\\css\\style.css', '/css/style.css'),
            array('', '..\\css\\.\\style.css', '/css/style.css'),
            array('', '..\\css\\..\\style.css', '/style.css'),
            array('', '..\\css\\.\\..\\style.css', '/style.css'),
            array('', '..\\css\\..\\.\\style.css', '/style.css'),

            // absolute paths
            array('/webmozart/puli', '/css/style.css', '/css/style.css'),
            array('/webmozart/puli', '\\css\\style.css', '/css/style.css'),
            array('C:/webmozart/puli', 'C:/css/style.css', 'C:/css/style.css'),
            array('D:/webmozart/puli', 'D:\\css\\style.css', 'D:/css/style.css'),
        ));
    }

    /**
     * @dataProvider provideMakeAbsoluteTests
     */
    public function testMakeAbsolute($basePath, $relativePath, $absolutePath)
    {
        $this->assertSame($absolutePath, Path::makeAbsolute($relativePath, $basePath));
    }

    /**
     * @expectedException \InvalidArgumentException
     */
    public function testMakeAbsoluteFailsIfBasePathNotAbsolute()
    {
        Path::makeAbsolute('css/style.css', 'webmozart/puli');
    }

    public function provideAbsolutePathsWithDifferentRoots()
    {
        return array(
            array('C:/css/style.css', '/webmozart/puli'),
            array('C:/css/style.css', '\\webmozart\\puli'),
            array('C:\\css\\style.css', '/webmozart/puli'),
            array('C:\\css\\style.css', '\\webmozart\\puli'),

            array('/css/style.css', 'C:/webmozart/puli'),
            array('/css/style.css', 'C:\\webmozart\\puli'),
            array('\\css\\style.css', 'C:/webmozart/puli'),
            array('\\css\\style.css', 'C:\\webmozart\\puli'),

            array('D:/css/style.css', 'C:/webmozart/puli'),
            array('D:/css/style.css', 'C:\\webmozart\\puli'),
            array('D:\\css\\style.css', 'C:/webmozart/puli'),
            array('D:\\css\\style.css', 'C:\\webmozart\\puli'),
        );
    }

    /**
     * @dataProvider provideAbsolutePathsWithDifferentRoots
     * @expectedException \InvalidArgumentException
     */
    public function testMakeAbsoluteFailsIfDifferentRoot($basePath, $relativePath)
    {
        Path::makeAbsolute($relativePath, $basePath);
    }

    public function provideMakeRelativeTests()
    {
        $paths = array_map(function (array $arguments) {
            return array($arguments[2], $arguments[0], $arguments[1]);
        }, $this->providePathTests());

        return array_merge($paths, array(
            array('/webmozart/puli/./css/style.css', '/webmozart/puli', 'css/style.css'),
            array('/webmozart/puli/../css/style.css', '/webmozart/puli', '../css/style.css'),
            array('/webmozart/puli/.././css/style.css', '/webmozart/puli', '../css/style.css'),
            array('/webmozart/puli/./../css/style.css', '/webmozart/puli', '../css/style.css'),
            array('/webmozart/puli/../../css/style.css', '/webmozart/puli', '../../css/style.css'),
            array('/webmozart/puli/css/style.css', '/webmozart/./puli', 'css/style.css'),
            array('/webmozart/puli/css/style.css', '/webmozart/../puli', '../webmozart/puli/css/style.css'),
            array('/webmozart/puli/css/style.css', '/webmozart/./../puli', '../webmozart/puli/css/style.css'),
            array('/webmozart/puli/css/style.css', '/webmozart/.././puli', '../webmozart/puli/css/style.css'),
            array('/webmozart/puli/css/style.css', '/webmozart/../../puli', '../webmozart/puli/css/style.css'),

            array('\\webmozart\\puli\\css\\style.css', '\\webmozart\\puli', 'css/style.css'),
            array('\\webmozart\\css\\style.css', '\\webmozart\\puli', '../css/style.css'),
            array('\\css\\style.css', '\\webmozart\\puli', '../../css/style.css'),

            array('C:/webmozart/puli/css/style.css', 'C:/webmozart/puli', 'css/style.css', ),
            array('C:/webmozart/css/style.css', 'C:/webmozart/puli', '../css/style.css'),
            array('C:/css/style.css', 'C:/webmozart/puli', '../../css/style.css'),

            array('C:\\webmozart\\puli\\css\\style.css', 'C:\\webmozart\\puli', 'css/style.css', ),
            array('C:\\webmozart\\css\\style.css', 'C:\\webmozart\\puli', '../css/style.css'),
            array('C:\\css\\style.css', 'C:\\webmozart\\puli', '../../css/style.css'),

            // already relative
            array('css/style.css', '/webmozart/puli', 'css/style.css'),
            array('css\\style.css', '\\webmozart\\puli', 'css/style.css'),

            // both relative
            array('css/style.css', 'webmozart/puli', '../../css/style.css'),
            array('css\\style.css', 'webmozart\\puli', '../../css/style.css'),

            // different slashes in path and base path
            array('/webmozart/puli/css/style.css', '\\webmozart\\puli', 'css/style.css'),
            array('\\webmozart\\puli\\css\\style.css', '/webmozart/puli', 'css/style.css'),
        ));
    }

    /**
     * @dataProvider provideMakeRelativeTests
     */
    public function testMakeRelative($absolutePath, $basePath, $relativePath)
    {
        $this->assertSame($relativePath, Path::makeRelative($absolutePath, $basePath));
    }

    /**
     * @expectedException \InvalidArgumentException
     */
    public function testMakeRelativeFailsIfBasePathNotAbsolute()
    {
        Path::makeRelative('/webmozart/puli/css/style.css', 'webmozart/puli');
    }

    /**
     * @dataProvider provideAbsolutePathsWithDifferentRoots
     * @expectedException \InvalidArgumentException
     */
    public function testMakeRelativeFailsIfDifferentRoot($absolutePath, $basePath)
    {
        Path::makeRelative($absolutePath, $basePath);
    }

    public function provideIsLocalTests()
    {
        return array(
            array('/bg.png', true),
            array('bg.png', true),
            array('http://example.com/bg.png', false),
            array('http://example.com', false),
        );
    }

    /**
     * @dataProvider provideIsLocalTests
     */
    public function testIsLocal($path, $isLocal)
    {
        $this->assertSame($isLocal, Path::isLocal($path));
    }
}
