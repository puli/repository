<?php

/*
 * This file is part of the Puli package.
 *
 * (c) Bernhard Schussek <bschussek@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Webmozart\Puli\Path;

/**
 * @since  1.0
 * @author Bernhard Schussek <bschussek@gmail.com>
 */
class Path
{
    /**
     * @param string $path
     *
     * @return string
     */
    public static function canonicalize($path)
    {
        $path = str_replace('\\', '/', $path);
        $parts = array_filter(explode('/', $path), 'strlen');
        $canonicalParts = array();

        foreach ($parts as $part) {
            if ('.' === $part) {
                continue;
            }

            if ('..' === $part) {
                array_pop($canonicalParts);

                continue;
            }

            $canonicalParts[] = $part;
        }

        return ('/' === $path[0] ? '/' : '').implode('/', $canonicalParts);
    }

    /**
     * @param string $path
     *
     * @return string
     */
    public static function dirname($path)
    {
        $parentPath = dirname($path);

        // Fix: dirname('/path') returns '\' on Windows
        if ('\\' === $parentPath) {
            $parentPath = '/';
        }

        return $parentPath;
    }

    private function __construct()
    {
    }
}
