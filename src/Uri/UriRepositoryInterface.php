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

use Webmozart\Puli\ResourceRepositoryInterface;

/**
 * A repository which supports URIs for retrieving resources.
 *
 * @since  1.0
 * @author Bernhard Schussek <bschussek@gmail.com>
 */
interface UriRepositoryInterface extends ResourceRepositoryInterface
{
    /**
     * Returns the supported URI schemes.
     *
     * The URI scheme is the part before the "://" in the URL.
     *
     * @return string[] The supported URI schemes.
     */
    public function getSupportedSchemes();

    /**
     * Returns the scheme prepended when a path is passed instead of a URI.
     *
     * @return string|null The default scheme or null if no schemes are
     *                     supported.
     */
    public function getDefaultScheme();
}
