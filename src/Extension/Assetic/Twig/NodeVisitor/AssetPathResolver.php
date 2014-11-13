<?php

/*
 * This file is part of the Puli package.
 *
 * (c) Bernhard Schussek <bschussek@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Puli\Extension\Assetic\Twig\NodeVisitor;

use Assetic\Extension\Twig\LazyAsseticNode;
use Puli\Extension\Twig\NodeVisitor\AbstractPathResolver;
use Puli\Extension\Twig\PuliExtension;

/**
 * @since  1.0
 * @author Bernhard Schussek <bschussek@gmail.com>
 */
class AssetPathResolver extends AbstractPathResolver
{
    /**
     * Returns the priority for this visitor.
     *
     * Priority should be between -10 and 10 (0 is the default).
     *
     * @return integer The priority level
     */
    public function getPriority()
    {
        return PuliExtension::RESOLVE_PATHS;
    }

    /**
     * @param \Twig_NodeInterface $node
     *
     * @return \Twig_NodeInterface
     */
    protected function processNode(\Twig_NodeInterface $node)
    {
        if ($node instanceof LazyAsseticNode) {
            $inputs = $node->getAttribute('inputs');

            foreach ($inputs as $key => $value) {
                $inputs[$key] = $this->resolvePath($value);
            }

            $node->setAttribute('inputs', $inputs);
        }
    }
}
