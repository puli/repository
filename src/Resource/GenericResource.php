<?php

/*
 * This file is part of the puli/repository package.
 *
 * (c) Bernhard Schussek <bschussek@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Puli\Repository\Resource;

/**
 * An in-memory directory in the repository.
 *
 * This class is mostly used for repository directories that are created on
 * demand:
 *
 * ```php
 * use Puli\Repository\InMemoryRepository;
 *
 * $repo = new InMemoryRepository();
 * $repo->add('/webmozart/puli/file', $resource);
 *
 * // implies:
 * $repo->add('/', new GenericResource());
 * $repo->add('/webmozart', new GenericResource());
 * $repo->add('/webmozart/puli', new GenericResource());
 * ```
 *
 * @since  1.0
 * @author Bernhard Schussek <bschussek@gmail.com>
 */
class GenericResource extends AbstractResource
{
}
