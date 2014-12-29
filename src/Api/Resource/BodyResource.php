<?php

/*
 * This file is part of the puli/repository package.
 *
 * (c) Bernhard Schussek <bschussek@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Puli\Repository\Api\Resource;

/**
 * A resource that contains a body.
 *
 * @since  1.0
 * @author Bernhard Schussek <bschussek@gmail.com>
 */
interface BodyResource extends Resource
{
    /**
     * Returns the body of the resource.
     *
     * @return string The resource body.
     */
    public function getBody();

    /**
     * Returns the size of the body in bytes.
     *
     * @return integer The body size in bytes.
     */
    public function getSize();
}
