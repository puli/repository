<?php

/*
 * This file is part of the Puli package.
 *
 * (c) Bernhard Schussek <bschussek@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Webmozart\Puli\Repository;

use Webmozart\Puli\Pattern\GlobPattern;
use Webmozart\Puli\Pattern\PatternInterface;
use Webmozart\Puli\Resource\DirectoryResource;
use Webmozart\Puli\Resource\FileResource;

/**
 * @since  %%NextVersion%%
 * @author Bernhard Schussek <bschussek@gmail.com>
 */
class ResourceRepository implements ResourceRepositoryInterface
{
    private $paths = array();

    /**
     * @var \Webmozart\Puli\Resource\ResourceInterface[]
     */
    private $resources = array();

    /**
     * {@inheritdoc}
     */
    public function get($repositoryPath)
    {
        if (!isset($this->resources[$repositoryPath])) {
            throw new ResourceNotFoundException(sprintf(
                'The resource "%s" does not exist.',
                $repositoryPath
            ));
        }

        return $this->resources[$repositoryPath];
    }

    public function getByTag($tag)
    {

    }

    public function listDirectory($repositoryPath)
    {

    }

    public function add($repositoryPath, $realPath)
    {
        if (is_string($realPath) && false !== strpos($realPath, '*')) {
            $realPath = glob($realPath);
        }

        if (is_array($realPath)) {
            foreach ($realPath as $path) {
                $this->add($repositoryPath.'/'.basename($path), $path);
            }

            return;
        }

        if (!is_string($realPath)) {
            throw new \InvalidArgumentException(sprintf(
                'The argument $realPath should be a string or an array, but is: %s.',
                gettype($realPath)
            ));
        }

        $isDirectory = is_dir($realPath);

        // Recursively add directory contents
        if ($isDirectory) {
            $iterator = new \FilesystemIterator($realPath, \FilesystemIterator::SKIP_DOTS | \FilesystemIterator::CURRENT_AS_PATHNAME);

            foreach ($iterator as $path) {
                $this->add($repositoryPath.'/'.basename($path), $path);
            }
        }

        // Create new Resource instances if necessary
        if (!isset($this->paths[$repositoryPath])) {
            $this->paths[$repositoryPath] = array($realPath);

            $this->resources[$repositoryPath] = $isDirectory
                ? new DirectoryResource(
                    $repositoryPath,
                    array($realPath)
                )
                : new FileResource(
                    $repositoryPath,
                    $realPath
                );

            return;
        }

        $this->paths[$repositoryPath][] = $realPath;
        $this->resources[$repositoryPath]->refresh($this);
    }

    /**
     * {@inheritdoc}
     */
    public function contains($repositoryPath)
    {
        if (is_string($repositoryPath) && false !== strpos($repositoryPath, '*')) {
            $repositoryPath = new GlobPattern($repositoryPath);
        }

        if ($repositoryPath instanceof PatternInterface) {
            $staticPrefix = $repositoryPath->getStaticPrefix();
            $regExp = $repositoryPath->getRegularExpression();

            foreach ($this->resources as $repositoryPath => $resource) {
                if (0 !== strpos($repositoryPath, $staticPrefix)) {
                    continue;
                }

                if (!preg_match($regExp, $repositoryPath)) {
                    continue;
                }

                return true;
            }

            return false;
        }

        if (is_array($repositoryPath)) {
            foreach ($repositoryPath as $path) {
                if (!$this->contains($path)) {
                    return false;
                }
            }

            return true;
        }

        return isset($this->resources[$repositoryPath]);
    }

    public function remove($repositoryPath)
    {

    }

    public function tag($repositoryPath, $tag)
    {

    }

    public function untag($repositoryPath, $tag = null)
    {

    }

    public function getTags($repositoryPath = null)
    {

    }

    public function getPaths($repositoryPath)
    {
        return $this->paths[$repositoryPath];
    }
}
