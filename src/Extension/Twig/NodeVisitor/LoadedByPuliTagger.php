<?php

/*
 * This file is part of the Puli package.
 *
 * (c) Bernhard Schussek <bschussek@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Puli\Extension\Twig\NodeVisitor;

use Puli\Extension\Twig\Node\LoadedByPuliNode;

/**
 * Adds the "puli" attribute to all {@link \Twig_Module} nodes that were loaded
 * through the Puli loader.
 *
 * For these nodes, it is guaranteed that the "filename" attribute is a Puli
 * path.
 *
 * @since  1.0
 * @author Bernhard Schussek <bschussek@gmail.com>
 */
class LoadedByPuliTagger implements \Twig_NodeVisitorInterface
{
    /**
     * @var \Twig_Node_Module|null
     */
    private $moduleNode;

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
        if ($node instanceof \Twig_Node_Module) {
            $this->moduleNode = $node;
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
        // Tag the node if it contains a LoadedByPuliNode
        // This cannot be done in enterNode(), because only leaveNode() may
        // return false in order to remove a node
        if ($node instanceof LoadedByPuliNode) {
            if (null !== $this->moduleNode) {
                $this->moduleNode->setAttribute('puli', true);
            }

            // Remove that node from the final tree
            return false;
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
        // Should be launched very early on so that other visitors don't have
        // to deal with the LoadedByPuliNode
        return -10;
    }
}
