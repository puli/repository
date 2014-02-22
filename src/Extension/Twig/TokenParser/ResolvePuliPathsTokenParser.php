<?php

/*
 * This file is part of the Twig Puli Extension.
 *
 * (c) Bernhard Schussek <bschussek@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Webmozart\Puli\Extension\Twig\TokenParser;

use Twig_NodeInterface;
use Twig_Token;
use Twig_Error_Syntax;
use Twig_TokenParser;
use Webmozart\Puli\Extension\Twig\Node\ResolvePuliPathsNode;

/**
 * Turns the "{% resolve_puli_paths %}" token into an instance of
 * {@link ResolvePuliPathsNode}.
 *
 * @since  1.0
 * @author Bernhard Schussek <bschussek@gmail.com>
 */
class ResolvePuliPathsTokenParser extends Twig_TokenParser
{
    /**
     * Parses a token and returns a node.
     *
     * @param Twig_Token $token A Twig_Token instance
     *
     * @return Twig_NodeInterface A Twig_NodeInterface instance
     *
     * @throws Twig_Error_Syntax
     */
    public function parse(Twig_Token $token)
    {
        $this->parser->getStream()->next();

        return new ResolvePuliPathsNode();
    }

    /**
     * Gets the tag name associated with this token parser.
     *
     * @return string The tag name
     */
    public function getTag()
    {
        return 'resolve_puli_paths';
    }
}
