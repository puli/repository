<?php

/*
 * This file is part of the Puli package.
 *
 * (c) Bernhard Schussek <bschussek@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Webmozart\Puli\Extension\Twig\NodeVisitor;

use Webmozart\Puli\Path;
use Webmozart\Puli\ResourceRepositoryInterface;

/**
 * @since  1.0
 * @author Bernhard Schussek <bschussek@gmail.com>
 */
abstract class AbstractPathResolver implements \Twig_NodeVisitorInterface
{
    /**
     * @var ResourceRepositoryInterface
     */
    protected $repo;

    /**
     * @var string
     */
    protected $currentDir;

    public function __construct(ResourceRepositoryInterface $repo)
    {
        $this->repo = $repo;
    }

    /**
     * Called before child nodes are visited.
     *
     * @param \Twig_NodeInterface $node The node to visit
     * @param \Twig_Environment   $env  The Twig environment instance
     *
     * @return \Twig_NodeInterface The modified node
     */
    public function enterNode(\Twig_NodeInterface $node, \Twig_Environment $env)
    {
        // Remember the directory of the current file
        if ($node instanceof \Twig_Node_Module && $node->hasAttribute('puli')) {
            // Currently, it doesn't seem like Twig does recursive traversals
            // (i.e. starting the traversal of another module while a previous
            // one is still in progress). Thus we don't need to track existing
            // values here.
            $this->currentDir = Path::getDirectory($node->getAttribute('filename'));
        }

        return $node;
    }

    /**
     * Called after child nodes are visited.
     *
     * @param \Twig_NodeInterface $node The node to visit
     * @param \Twig_Environment   $env  The Twig environment instance
     *
     * @return \Twig_NodeInterface|false The modified node or false if the node must be removed
     */
    public function leaveNode(\Twig_NodeInterface $node, \Twig_Environment $env)
    {
        // Only process if the current directory was set
        if (null !== $this->currentDir) {
            $this->processNode($node);
        }

        return $node;
    }

    protected function resolvePath($path)
    {
        // Empty path? WTF I don't want to deal with this.
        if ('' === $path) {
            return $path;
        }

        // Absolute paths are fine
        if ('/' === $path[0]) {
            return $path;
        }

        // Resolve relative paths
        $absolutePath = Path::canonicalize($this->currentDir.'/'.$path);

        // With other loaders enabled, it may happen that a path looks like
        // a relative path, but is none, for example
        // "AcmeBlogBundle::index.html.twig", which doesn't start with a forward
        // slash. For this reason, we should only resolve paths if they actually
        // exist in the repository.
        if ($this->repo->contains($absolutePath)) {
            return $absolutePath;
        }

        return $path;
    }

    /**
     * @param \Twig_NodeInterface $node
     *
     * @return \Twig_NodeInterface
     */
    abstract protected function processNode(\Twig_NodeInterface $node);
}
