<?php

/*
 * This file is part of the Puli package.
 *
 * (c) Bernhard Schussek <bschussek@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Webmozart\Puli\Uri;

/**
 * @since  1.0
 * @author Bernhard Schussek <bschussek@gmail.com>
 */
class Uri
{
    public static function parse($uri)
    {
        $parts = explode('://', $uri, 2);

        if (!ctype_alpha($parts[0])) {
            throw new InvalidUriException(sprintf(
                'The URI "%s" is invalid. The scheme should consist of '.
                'alphabetic characters only.',
                $uri
            ));
        }

        if (!isset($parts[1])) {
            throw new InvalidUriException(sprintf(
                'The URI "%s" is invalid. The path should not be empty.',
                $uri
            ));
        }

        if ('/' !== $parts[1][0]) {
            throw new InvalidUriException(sprintf(
                'The URI "%s" is invalid. The path should start with a '.
                'forward slash ("/").',
                $uri
            ));
        }

        return array(
            'scheme' => $parts[0],
            'path' => $parts[1],
        );
    }

    private function __construct()
    {
    }
}
