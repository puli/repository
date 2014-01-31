<?php

/*
 * This file is part of the Puli package.
 *
 * (c) Bernhard Schussek <bschussek@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Webmozart\Puli\Dumper;

use Webmozart\Puli\Repository\ResourceRepositoryInterface;

/**
 * @since  %%NextVersion%%
 * @author Bernhard Schussek <bschussek@gmail.com>
 */
interface LocatorDumperInterface
{
    public function dumpLocator(ResourceRepositoryInterface $repository, $targetPath);
}
