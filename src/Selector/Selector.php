<?php

/*
 * This file is part of the puli/repository package.
 *
 * (c) Bernhard Schussek <bschussek@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Puli\Repository\Selector;

/**
 * Utility methods for handling path selectors.
 *
 * "Path selectors" are repository paths which may contain "*" as wildcard.
 *
 * @since  1.0
 * @author Bernhard Schussek <bschussek@gmail.com>
 */
class Selector
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
     * Returns whether a string is a selector.
     *
     * @param string $string The tested string.
     *
     * @return bool Returns `true` if the string is a selector, `false`
     *              otherwise.
     */
    public static function isSelector($string)
    {
        return false !== strpos($string, '*');
    }

    /**
     * Converts a selector to a regular expression.
     *
     * @param string $selector A path selector in canonical form.
     *
     * @return string The regular expression for matching the selector.
     */
    public static function toRegEx($selector)
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
        $quoted = preg_quote($selector, '~');

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
     * Returns the static prefix of a selector.
     *
     * The "static prefix" are all characters up to the first wildcard "*".
     * If the selector does not contain wildcards, the full selector is
     * returned.
     *
     * @param string $selector A path selector in canonical form.
     *
     * @return string The static prefix of the selector.
     */
    public static function getStaticPrefix($selector)
    {
        if (false !== ($pos = strpos($selector, '*'))) {
            return substr($selector, 0, $pos);
        }

        return $selector;
    }

    /**
     * Returns the base path of a selector.
     *
     * The "base path" is the longest path trailed by a "/" on the left of the
     * first wildcard "*". If the selector does not contain wildcards, the
     * directory name of the selector is returned.
     *
     * ```php
     * Selector::getBasePath('/css/*.css');
     * // => /css
     *
     * Selector::getBasePath('/css/style.css');
     * // => /css
     *
     * Selector::getBasePath('/css/st*.css');
     * // => /css
     *
     * Selector::getBasePath('/*.css');
     * // => /
     * ```
     *
     * @param string $selector A path selector in canonical form.
     *
     * @return string The base path of the selector.
     */
    public static function getBasePath($selector)
    {
        // Start searching for a "/" at the last character
        $offset = -1;

        // If the selector contains a wildcard "*", start searching for the
        // "/" on the left of the wildcard
        if (false !== ($pos = strpos($selector, '*'))) {
            $offset = $pos - strlen($selector);
        }

        if (false !== ($pos = strrpos($selector, '/', $offset))) {
            // Special case: Return "/" if the only slash is at the beginning
            // of the selector
            if (0 === $pos) {
                return '/';
            }

            return substr($selector, 0, $pos);
        }

        // Selector contains no slashes on the left of the wildcard
        // Return an empty string
        return '';
    }

    private function __construct()
    {
    }
}
