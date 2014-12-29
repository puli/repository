<?php

/*
 * This file is part of the puli/repository package.
 *
 * (c) Bernhard Schussek <bschussek@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Puli\Repository\Glob;

/**
 * Utility methods for handling globs.
 *
 * @since  1.0
 * @author Bernhard Schussek <bschussek@gmail.com>
 */
class Glob
{
    /**
     * Represents a literal "\" in a regular expression.
     */
    const BACKSLASH = '\\\\';

    /**
     * Represents a literal "*" in a regular expression.
     */
    const STAR = '\\*';

    /**
     * Matches a literal "\" when running a regular expression against another
     * regular expression.
     */
    const E_BACKSLASH = '\\\\\\\\';

    /**
     * Matches a literal "*" when running a regular expression against another
     * regular expression.
     */
    const E_STAR = '\\\\\\*';

    /**
     * Returns whether a string is a glob.
     *
     * @param string $string The tested string.
     *
     * @return bool Returns `true` if the string is a glob, `false`
     *              otherwise.
     */
    public static function isGlob($string)
    {
        return false !== strpos($string, '*');
    }

    /**
     * Converts a glob to a regular expression.
     *
     * @param string $glob A path glob in canonical form.
     *
     * @return string The regular expression for matching the glob.
     */
    public static function toRegEx($glob)
    {
        // From the PHP manual: To specify a literal single quote, escape it
        // with a backslash (\). To specify a literal backslash, double it (\\).
        // All other instances of backslash will be treated as a literal backslash.

        // This method does the following replacements:

        // Normal wildcards:    "*"  => ".*" (regex match any)
        // Escaped wildcards:   "\*" => "\*" (regex star)
        // Escaped backslashes: "\\" => "\\" (regex backslash)

        // Other characters are escaped as usual for regular expressions.

        // Quote regex characters
        $quoted = preg_quote($glob, '~');

        // Replace "*" by ".*", as long as preceded by an even number of backslashes
        $regEx = preg_replace(
            '~(?<!'.self::E_BACKSLASH.')(('.self::E_BACKSLASH.self::E_BACKSLASH.')*)'.self::E_STAR.'~',
            '$1.*',
            $quoted
        );

        // Replace "\*" by "*"
        $regEx = str_replace(self::BACKSLASH.self::STAR, self::STAR, $regEx);

        // Replace "\\\\" by "\\"
        // (escaped backslashes were escaped again by preg_quote())
        $regEx = str_replace(self::E_BACKSLASH, self::BACKSLASH, $regEx);

        return '~^'.$regEx.'$~';
    }

    /**
     * Returns the static prefix of a glob.
     *
     * The "static prefix" are all characters up to the first wildcard "*".
     * If the glob does not contain wildcards, the full glob is
     * returned.
     *
     * @param string $glob A path glob in canonical form.
     *
     * @return string The static prefix of the glob.
     */
    public static function getStaticPrefix($glob)
    {
        if (false !== ($pos = strpos($glob, '*'))) {
            return substr($glob, 0, $pos);
        }

        return $glob;
    }

    /**
     * Returns the base path of a glob.
     *
     * The "base path" is the longest path trailed by a "/" on the left of the
     * first wildcard "*". If the glob does not contain wildcards, the
     * directory name of the glob is returned.
     *
     * ```php
     * Glob::getBasePath('/css/*.css');
     * // => /css
     *
     * Glob::getBasePath('/css/style.css');
     * // => /css
     *
     * Glob::getBasePath('/css/st*.css');
     * // => /css
     *
     * Glob::getBasePath('/*.css');
     * // => /
     * ```
     *
     * @param string $glob A path glob in canonical form.
     *
     * @return string The base path of the glob.
     */
    public static function getBasePath($glob)
    {
        // Start searching for a "/" at the last character
        $offset = -1;

        // If the glob contains a wildcard "*", start searching for the
        // "/" on the left of the wildcard
        if (false !== ($pos = strpos($glob, '*'))) {
            $offset = $pos - strlen($glob);
        }

        if (false !== ($pos = strrpos($glob, '/', $offset))) {
            // Special case: Return "/" if the only slash is at the beginning
            // of the glob
            if (0 === $pos) {
                return '/';
            }

            return substr($glob, 0, $pos);
        }

        // Glob contains no slashes on the left of the wildcard
        // Return an empty string
        return '';
    }

    /**
     * Matches a path against a glob.
     *
     * @param string $path The path.
     * @param string $glob The glob.
     *
     * @return bool Returns `true` if the path is matched by the glob.
     */
    public static function match($path, $glob)
    {
        if (false === strpos($glob, '*')) {
            return $glob === $path;
        }

        if (0 !== strpos($path, self::getStaticPrefix($glob))) {
            return false;
        }

        if (!preg_match(self::toRegEx($glob), $path)) {
            return false;
        }

        return true;
    }

    /**
     * Filters paths matching a glob.
     *
     * @param string[] $paths A list of paths.
     * @param string   $glob  The glob.
     *
     * @return string[] The paths matching the glob.
     */
    public static function filter(array $paths, $glob)
    {
        if (false === strpos($glob, '*')) {
            return in_array($glob, $paths) ? array($glob) : array();
        }

        $staticPrefix = self::getStaticPrefix($glob);
        $regExp = self::toRegEx($glob);

        return array_filter($paths, function ($path) use ($staticPrefix, $regExp) {
            return 0 === strpos($path, $staticPrefix) && preg_match($regExp, $path);
        });
    }

    private function __construct()
    {
    }
}
