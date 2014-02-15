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

/**
 * @since  1.0
 * @author Bernhard Schussek <bschussek@gmail.com>
 */
class NoDirectoryException extends \Exception
{
    public function __construct($directory, $code = 0, \Exception $previous = null)
    {
        parent::__construct(sprintf(
            'The path "%s" is not a directory.',
            $directory
        ), $code, $previous);
    }
}
