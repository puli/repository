<?php

/*
 * This file is part of the Puli package.
 *
 * (c) Bernhard Schussek <bschussek@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Webmozart\Puli;

/**
 * Utility methods for handling path strings.
 *
 * The methods in this class are able to deal with both UNIX and Windows paths
 * with both forward and backward slashes. All methods return normalized parts
 * containing only forward slashes and no excess "." and ".." segments.
 *
 * @since  1.0
 * @author Bernhard Schussek <bschussek@gmail.com>
 */
class Path
{
    /**
     * Canonicalizes the given path.
     *
     * During normalization, all slashes are replaced by forward slashes ("/").
     * Furthermore, all "." and ".." segments are removed as far as possible.
     * ".." segments at the beginning of relative paths are not removed.
     *
     *     echo Path::normalize("\webmozart\puli\..\css\style.css");
     *     // => /webmozart/style.css
     *
     *     echo Path::normalize("../css/./style.css");
     *     // => ../css/style.css
     *
     * This method is able to deal with both UNIX and Windows paths.
     *
     * @param string $path A path string
     *
     * @return string The canonical path
     */
    public static function canonicalize($path)
    {
        $path = (string) $path;

        if ('' === $path) {
            return '';
        }

        $path = str_replace('\\', '/', $path);

        list ($root, $path) = self::split($path);

        $parts = array_filter(explode('/', $path), 'strlen');
        $canonicalParts = array();

        // Collapse "." and "..", if possible
        foreach ($parts as $part) {
            if ('.' === $part) {
                continue;
            }

            // Collapse ".." with the previous part, if one exists
            // Don't collapse ".." if the previous part is also ".."
            if ('..' === $part && count($canonicalParts) > 0
                    && '..' !== $canonicalParts[count($canonicalParts)-1]) {
                array_pop($canonicalParts);

                continue;
            }

            // Only add ".." prefixes for relative paths
            if ('..' !== $part || '' === $root) {
                $canonicalParts[] = $part;
            }
        }

        // Add the root directory again
        return $root.implode('/', $canonicalParts);
    }

    /**
     * Returns the directory part of the path.
     *
     * This method is similar to PHP's dirname(), but handles various cases
     * where dirname() returns a weird result:
     *
     *  - dirname() does not accept backslashes on UNIX
     *  - dirname("C:/webmozart") returns "C:", not "C:/"
     *  - dirname("C:/") returns ".", not "C:/"
     *  - dirname("C:") returns ".", not "C:/"
     *  - dirname("webmozart") returns ".", not ""
     *  - dirname() does not canonicalize the result
     *
     * This method fixes these shortcomings and behaves like dirname()
     * otherwise.
     *
     * The result is a canonical path.
     *
     * @param string $path A path string
     *
     * @return string The canonical directory part. Returns the root directory
     *                if the root directory is passed. Returns an empty string
     *                if a relative path is passed that contains no slashes.
     *                Returns an empty string if an empty string is passed
     */
    public static function getDirectory($path)
    {
        if ('' === $path) {
            return '';
        }

        $path = static::canonicalize($path);

        if (false !== ($pos = strrpos($path, '/'))) {
            // Directory equals root directory "/"
            if (0 === $pos) {
                return '/';
            }

            // Directory equals Windows root "C:/"
            if (2 === $pos && ctype_alpha($path[0]) && ':' === $path[1]) {
                return substr($path, 0, 3);
            }

            return substr($path, 0, $pos);
        }

        return '';
    }

    /**
     * Returns the root directory of a path.
     *
     * The result is a canonical path.
     *
     * @param string $path A path string
     *
     * @return string The canonical root directory. Returns an empty string if
     *                the given path is relative or empty
     */
    public static function getRoot($path)
    {
        if ('' === $path) {
            return '';
        }

        // UNIX root "/" or "\" (Windows style)
        if ('/' === $path[0] || '\\' === $path[0]) {
            return '/';
        }

        $length = strlen($path);

        // Windows root
        if ($length > 1 && ctype_alpha($path[0]) && ':' === $path[1]) {
            // Special case: "C:"
            if (2 === $length) {
                return $path.'/';
            }

            // Normal case: "C:/ or "C:\"
            if ('/' === $path[2] || '\\' === $path[2]) {
                return $path[0].$path[1].'/';
            }
        }

        return '';
    }

    /**
     * Returns whether a path is absolute.
     *
     * @param string $path A path string
     *
     * @return boolean Returns true if the path is absolute, false if it is
     *                 relative or empty
     */
    public static function isAbsolute($path)
    {
        if ('' === $path) {
            return false;
        }

        // UNIX root "/" or "\" (Windows style)
        if ('/' === $path[0] || '\\' === $path[0]) {
            return true;
        }

        // Windows root
        if (strlen($path) > 1 && ctype_alpha($path[0]) && ':' === $path[1]) {
            // Special case: "C:"
            if (2 === strlen($path)) {
                return true;
            }

            // Normal case: "C:/ or "C:\"
            if ('/' === $path[2] || '\\' === $path[2]) {
                return true;
            }
        }

        return false;
    }

    /**
     * Returns whether a path is relative.
     *
     * @param string $path A path string
     *
     * @return boolean Returns true if the path is relative or empty, false if
     *                 it is absolute
     */
    public static function isRelative($path)
    {
        return !static::isAbsolute($path);
    }

    /**
     * Turns a relative path into an absolute path.
     *
     * Usually, the relative path is appended to the given base path. Dot
     * segments ("." and "..") are removed/collapsed and all slashes turned
     * into forward slashes.
     *
     *     echo Path::makeAbsolute("../style.css", "/webmozart/puli/css");
     *     // => /webmozart/puli/style.css
     *
     * If an absolute path is passed, that path is returned unless its root
     * directory is different than the one of the base path. In that case, an
     * exception is thrown.
     *
     *     Path::makeAbsolute("/style.css", "/webmozart/puli/css");
     *     => /style.css
     *
     *     Path::makeAbsolute("C:/style.css", "C:/webmozart/puli/css");
     *     => C:/style.css
     *
     *     Path::makeAbsolute("C:/style.css", "/webmozart/puli/css");
     *     // InvalidArgumentException
     *
     * If the base path is not an absolute path, an exception is thrown.
     *
     * The result is a canonical path.
     *
     * @param string $path     A path to make absolute
     * @param string $basePath An absolute base path
     *
     * @return string An absolute path in canonical form
     *
     * @throws \InvalidArgumentException If the base path is not absolute or if
     *                                   the given path is an absolute path with
     *                                   a different root than the base path
     */
    public static function makeAbsolute($path, $basePath)
    {
        $basePath = (string) $basePath;

        if ('' !== $basePath && !static::isAbsolute($basePath)) {
            throw new \InvalidArgumentException(sprintf(
                'The base path "%s" is not an absolute path.',
                $basePath
            ));
        }

        if (static::isAbsolute($path)) {
            $root = static::getRoot($path);
            $baseRoot = static::getRoot($basePath);

            if ($root !== $baseRoot) {
                throw new \InvalidArgumentException(sprintf(
                    'The path "%s" cannot be made absolute based on "%s", '.
                    'because their roots are different ("%s" and "%s").',
                    $path,
                    $basePath,
                    $root,
                    $baseRoot
                ));
            }

            return static::canonicalize($path);
        }

        return static::canonicalize($basePath.'/'.$path);
    }

    /**
     * Turns a path into a relative path.
     *
     * The relative path is created relative to the given base path:
     *
     *     echo Path::makeRelative("/webmozart/style.css", "/webmozart/puli");
     *     // => ../style.css
     *
     * If a relative path is passed and the base path is absolute, the relative
     * path is returned unchanged:
     *
     *     Path::makeRelative("style.css", "/webmozart/puli/css");
     *     => style.css
     *
     * If both paths are relative, the relative path is created with the
     * assumption that both paths are relative to the same directory:
     *
     *     Path::makeRelative("style.css", "webmozart/puli/css");
     *     => ../../../style.css
     *
     * If both paths are absolute, their root directory must be the same,
     * otherwise an exception is thrown:
     *
     *     Path::makeRelative("C:/webmozart/style.css", "/webmozart/puli");
     *     // InvalidArgumentException
     *
     * If the passed path is absolute, but the base path is not, an exception
     * is thrown as well:
     *
     *     Path::makeRelative("/webmozart/style.css", "webmozart/puli");
     *     // InvalidArgumentException
     *
     * If the base path is not an absolute path, an exception is thrown.
     *
     * The result is a canonical path.
     *
     * @param string $path     A path to make relative
     * @param string $basePath An base path
     *
     * @return string A relative path in canonical form
     *
     * @throws \InvalidArgumentException If the base path is not absolute or if
     *                                   the given path has a different root
     *                                   than the base path
     */
    public static function makeRelative($path, $basePath)
    {
        $path = static::canonicalize($path);

        list ($root, $relativePath) = self::split($path);

        $basePath = static::canonicalize($basePath);

        list ($baseRoot, $relativeBasePath) = self::split($basePath);

        // If the base path is given as absolute path and the path is already
        // relative, consider it to be relative to the given absolute path
        // already
        if ('' === $root && '' !== $baseRoot) {
            return $relativePath;
        }

        // If the passed path is absolute, but the base path is not, we
        // cannot generate a relative path
        if ('' !== $root && '' === $baseRoot) {
            throw new \InvalidArgumentException(sprintf(
                'The absolute path "%s" cannot be made relative to the '.
                'relative path "%s". You should provide an absolute base '.
                'path instead.',
                $path,
                $basePath
            ));
        }

        // Fail if the roots of the two paths are different
        if ($baseRoot && $root !== $baseRoot) {
            throw new \InvalidArgumentException(sprintf(
                'The path "%s" cannot be made relative to "%s", because they '.
                'have different roots ("%s" and "%s").',
                $path,
                $basePath,
                $root,
                $baseRoot
            ));
        }

        if ('' === $relativeBasePath) {
            return $relativePath;
        }

        // Build a "../../" prefix with as many "../" parts as necessary
        $parts = explode('/', $relativePath);
        $baseParts = explode('/', $relativeBasePath);
        $dotDotPrefix = '';

        foreach ($baseParts as $i => $basePart) {
            if ($basePart === $parts[$i]) {
                unset($parts[$i]);

                continue;
            }

            $dotDotPrefix .= '../';
        }

        return $dotDotPrefix.implode('/', $parts);
    }

    /**
     * Returns whether the given path is on the local filesystem.
     *
     * @param string $path A path string
     *
     * @return boolean Returns true if the path is local, false for a URL
     */
    public static function isLocal($path)
    {
        return false === strpos($path, '://');
    }

    /**
     * Splits a part into its root directory and the remainder.
     *
     * If the path has no root directory, an empty root directory will be
     * returned.
     *
     * If the root directory is a Windows style partition, the resulting root
     * will always contain a trailing slash.
     *
     *     list ($root, $path) = Path::split("C:/webmozart")
     *     // => array("C:/", "webmozart")
     *
     *     list ($root, $path) = Path::split("C:")
     *     // => array("C:/", "")
     *
     * @param string $path The path to split
     *
     * @return array An array with the root directory and the remaining relative
     *               path
     */
    private static function split($path)
    {
        $root = '';
        $length = strlen($path);

        // Remove and remember root directory
        if ('/' === $path[0]) {
            $root = '/';
            $path = $length > 1 ? substr($path, 1) : '';
        } elseif ($length > 1 && ctype_alpha($path[0]) && ':' === $path[1]) {
            if (2 === $length) {
                // Windows special case: "C:"
                $root = $path.'/';
                $path = '';
            } elseif ('/' === $path[2]) {
                // Windows normal case: "C:/"..
                $root = substr($path, 0, 3);
                $path = $length > 3 ? substr($path, 3) : '';
            }
        }

        return array($root, $path);
    }

    private function __construct()
    {
    }
}
