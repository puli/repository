<?php

/*
 * This file is part of the Puli package.
 *
 * (c) Bernhard Schussek <bschussek@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Puli\Util;

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
        return '~^'.str_replace('\*', '[^/]+', preg_quote($selector, '~')).'$~';
    }

    /**
     * Converts a selector to a glob pattern.
     *
     * The flag {@link GLOB_BRACE} must be used if this pattern is passed to
     * {@link glob}.
     *
     * @param string $selector A path selector in canonical form.
     *
     * @return string The glob for find files for the selector.
     */
    public static function toGlob($selector)
    {
        // Return hidden files (starting with ".") when the "*" wildcard is
        // used in directories
        return str_replace('/*', '/{.,}*', $selector);
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

    private function __construct()
    {
    }
}
