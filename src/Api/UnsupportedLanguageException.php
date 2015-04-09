<?php

/*
 * This file is part of the puli/repository package.
 *
 * (c) Bernhard Schussek <bschussek@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Puli\Repository\Api;

use Exception;
use RuntimeException;

/**
 * Thrown when a glob language is not supported by the repository.
 *
 * @since  1.0
 * @author Bernhard Schussek <bschussek@gmail.com>
 */
class UnsupportedLanguageException extends RuntimeException
{
    /**
     * Creates an exception for an unsupported language string.
     *
     * @param string    $language The unsupported language.
     * @param Exception $cause    The exception that caused this exception.
     *
     * @return static The created exception.
     */
    public static function forLanguage($language, Exception $cause = null)
    {
        return new static(sprintf(
            'The language "%s" is not supported.',
            $language
        ), 0, $cause);
    }
}
