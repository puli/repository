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
 * Utility methods for handling URIs.
 *
 * @since  1.0
 * @author Bernhard Schussek <bschussek@gmail.com>
 */
class Uri
{
    /**
     * Parses a URI.
     *
     * The returned array contains the following keys:
     *
     *  * "scheme": The scheme part before the "://";
     *  * "path": The path part after the "://".
     *
     * The URI must fulfill a few constraints:
     *
     *  * the scheme must consist of alphabetic characters only;
     *  * the scheme may be omitted. Then "://" must be omitted too;
     *  * the path must not be empty;
     *  * the path must start with a forward slash ("/").
     *
     * If any of these constraints is not fulfilled, an
     * {@link InvalidUriException} is thrown.
     *
     * @param string $uri A URI string.
     *
     * @return array The parts of the URI.
     *
     * @throws InvalidUriException If the URI is invalid.
     */
    public static function parse($uri)
    {
        if (!is_string($uri)) {
            throw new InvalidUriException(sprintf(
                'The URI must be a string, but is a %s.',
                is_object($uri) ? get_class($uri) : gettype($uri)
            ));
        }

        if (false !== ($pos = strpos($uri, '://'))) {
            $parts = array(substr($uri, 0, $pos), substr($uri, $pos + 3));

            if (!ctype_alnum($parts[0])) {
                throw new InvalidUriException(sprintf(
                    'The URI "%s" is invalid. The scheme should consist of '.
                    'alphabetic characters only.',
                    $uri
                ));
            }

            if (!ctype_alpha($parts[0][0])) {
                throw new InvalidUriException(sprintf(
                    'The URI "%s" is invalid. The scheme should start with a letter.',
                    $uri
                ));
            }
        } else {
            $parts = array('', $uri);
        }

        if ('' === $parts[1]) {
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
