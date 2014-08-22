<?php

/*
 * This file is part of the Puli package.
 *
 * (c) Bernhard Schussek <bschussek@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Webmozart\Puli\Filesystem\PathFinder;

use Webmozart\Puli\Pattern\GlobPattern;
use Webmozart\Puli\Pattern\PatternInterface;

/**
 * @since  1.0
 * @author Bernhard Schussek <bschussek@gmail.com>
 */
class GlobFinder implements PathFinderInterface
{
    public function findPaths(PatternInterface $pattern)
    {
        if (!$pattern instanceof GlobPattern) {
            throw new \InvalidArgumentException(sprintf(
                'The "%s" expects patterns of class '.
                '"Webmozart\Puli\Pattern\GlobPattern". Got: "%s". '.
                'Maybe you should pass a matching finder to the constructor '.
                'of your FilesystemLocator?',
                __CLASS__,
                get_class($pattern)
            ));
        }

        return glob((string) $pattern);
    }
}
