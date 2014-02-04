<?php

/*
 * This file is part of the Puli package.
 *
 * (c) Bernhard Schussek <bschussek@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Webmozart\Puli\Tag;

use Webmozart\Puli\Resource\ResourceInterface;

/**
 * @since  1.0
 * @author Bernhard Schussek <bschussek@gmail.com>
 */
interface TagInterface extends \Traversable, \Countable
{
    public function getName();

    public function __toString();

    public function addResource(ResourceInterface $resource);

    public function removeResource(ResourceInterface $resource);

    /**
     * @return ResourceInterface[]
     */
    public function getResources();
}
