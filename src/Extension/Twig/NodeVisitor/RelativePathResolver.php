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

use Webmozart\Puli\Locator\ResourceLocatorInterface;
use Webmozart\Puli\Path\Path;
use Webmozart\Puli\Extension\Twig\Node\ResolvePuliPathsNode;

/**
 * @since  1.0
 * @author Bernhard Schussek <bschussek@gmail.com>
 */
class RelativePathResolver implements \Twig_NodeVisitorInterface
{
    /**
     * @var ResourceLocatorInterface
     */
    private $locator;

    /**
     * @var string
     */
    private $currentDir;

    /**
     * @var boolean
     */
    private $resolvePaths;

    public function __construct(ResourceLocatorInterface $locator)
    {
        $this->locator = $locator;
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
        if ($node instanceof \Twig_Node_Module) {
            // Currently, it doesn't seem like Twig does recursive traversals
            // (i.e. starting the traversal of another module while a previous
            // one is still in progress). Thus we don't need to track existing
            // values here.
            $this->currentDir = Path::getDirectory($node->getAttribute('filename'));
            $this->resolvePaths = false;
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
        // Activate processing of relative paths only if the node tree contains
        // a ResolvePuliPathsNode
        if ($node instanceof ResolvePuliPathsNode) {
            $this->resolvePaths = true;

            // Remove that node from the final tree
            return false;
        }

        // Ignore files without a ResolvePuliPathsNode
        if (!$this->resolvePaths) {
            return $node;
        }

        if ($node instanceof \Twig_Node_Module) {
            // Resolve relative parent template paths to absolute paths
            $parentNode = $node->getNode('parent');

            // If the template extends another template, resolve the path
            if ($parentNode instanceof \Twig_Node_Expression_Constant) {
                $this->resolveRepositoryPath($parentNode);
            }

            // Resolve paths of embedded templates
            foreach ($node->getAttribute('embedded_templates') as $embeddedNode) {
                /** @var \Twig_Node_Module $embeddedNode */
                $embedParent = $embeddedNode->getNode('parent');

                // If the template extends another template, resolve the path
                if ($embedParent instanceof \Twig_Node_Expression_Constant) {
                    $this->resolveRepositoryPath($embedParent);
                }
            }
        } elseif ($node instanceof \Twig_Node_Include) {
            $exprNode = $node->getNode('expr');

            if ($exprNode instanceof \Twig_Node_Expression_Constant) {
                $this->resolveRepositoryPath($exprNode);
            }
        }

        return $node;
    }

    /**
     * Returns the priority for this visitor.
     *
     * Priority should be between -10 and 10 (0 is the default).
     *
     * @return integer The priority level
     */
    public function getPriority()
    {
        return -5;
    }

    private function resolveRepositoryPath(\Twig_Node_Expression_Constant $node)
    {
        $templatePath = $node->getAttribute('value');

        // Empty path? WTF I don't want to deal with this.
        if ('' === $templatePath) {
            return;
        }

        // Absolute paths are fine
        if ('/' === $templatePath[0]) {
            return;
        }

        // Resolve relative paths
        $absolutePath = $this->currentDir.'/'.$templatePath;

        // With other loaders enabled, it may happen that a path looks like
        // a relative path, but is none, for example
        // "AcmeBlogBundle::index.html.twig", which doesn't start with a forward
        // slash. For this reason, we should only resolve paths if they actually
        // exist in the repository.
        if ($this->locator->contains($absolutePath)) {
            $node->setAttribute('value', $absolutePath);
        }
    }
}
