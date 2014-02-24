<?php

/*
 * This file is part of the Puli package.
 *
 * (c) Bernhard Schussek <bschussek@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Webmozart\Puli\Tests\Extension\Twig;

/**
 * @since  1.0
 * @author Bernhard Schussek <bschussek@gmail.com>
 */
class RandomizedTwigEnvironment extends \Twig_Environment
{
    private static $previousPrefixes = array();

    public function __construct(\Twig_LoaderInterface $loader = null, $options = array())
    {
        parent::__construct($loader, $options);

        // Make sure the template class prefix is different for every new
        // instance of this class to isolate the tests
        do {
            $this->templateClassPrefix = '__TwigTemplate_'.rand(10000, 99999).'_';
        } while (isset(self::$previousPrefixes[$this->templateClassPrefix]));

        self::$previousPrefixes[$this->templateClassPrefix] = true;
    }

}
