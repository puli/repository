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
 * @since  1.0
 * @author Bernhard Schussek <bschussek@gmail.com>
 */
interface ManageableRepositoryInterface extends ResourceRepositoryInterface
{
    public function add($path, $resource);

    public function remove($selector);

    public function tag($selector, $tag);

    public function untag($selector, $tag = null);
}
