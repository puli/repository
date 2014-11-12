<?php

/*
 * This file is part of the Puli package.
 *
 * (c) Bernhard Schussek <bschussek@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Webmozart\Puli\Resource;

use Webmozart\Puli\ResourceRepositoryInterface;
use Webmozart\Puli\UnsupportedResourceException;

/**
 * @since  1.0
 * @author Bernhard Schussek <bschussek@gmail.com>
 */
interface AttachableResourceInterface extends ResourceInterface
{
    /**
     * @param ResourceRepositoryInterface $repo
     * @param                             $path
     */
    public function attachTo(ResourceRepositoryInterface $repo, $path);

    public function detach();

    /**
     * @param ResourceInterface $resource
     *
     * @throws UnsupportedResourceException
     */
    public function override(ResourceInterface $resource);
}
