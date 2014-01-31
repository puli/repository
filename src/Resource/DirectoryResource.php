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

/**
 * @since  %%NextVersion%%
 * @author Bernhard Schussek <bschussek@gmail.com>
 */
class DirectoryResource implements ResourceInterface
{
    /**
     * @var string
     */
    private $repositoryPath;

    /**
     * @var string[]
     */
    private $paths;

    public function __construct($repositoryPath, array $paths)
    {
        $this->repositoryPath = $repositoryPath;
        $this->paths = $paths;
    }

    /**
     * @return string
     */
    public function getRepositoryPath()
    {
        return $this->repositoryPath;
    }

    /**
     * @return string[]
     */
    public function getPaths()
    {
        return $this->paths;
    }
}
