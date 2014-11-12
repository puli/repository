<?php

/*
 * This file is part of the Puli package.
 *
 * (c) Bernhard Schussek <bschussek@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Webmozart\Puli\Util;

/**
 * @since  1.0
 * @author Bernhard Schussek <bschussek@gmail.com>
 */
class Selector
{
    public static function toRegEx($selector)
    {
        return '~^'.str_replace('\*', '[^/]+', preg_quote($selector, '~')).'$~';
    }

    public static function toGlob($selector)
    {
        // Return hidden files (starting with ".") when the "*" wildcard is
        // used in directories
        return str_replace('/*', '/{.,}*', $selector);
    }

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
