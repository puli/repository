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

use Webmozart\Puli\Repository\ResourceRepositoryInterface;

/**
 * @since  %%NextVersion%%
 * @author Bernhard Schussek <bschussek@gmail.com>
 */
class DirectoryResource extends AbstractResource
{
    private $contents = array();

    public function refresh(ResourceRepositoryInterface $repository)
    {
        $paths = $repository->getPaths($this->repositoryPath);

        $this->path = array_pop($paths);
        $this->alternativePaths = $paths;
    }
}
