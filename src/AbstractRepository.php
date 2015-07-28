<?php

/*
 * This file is part of the puli/repository package.
 *
 * (c) Bernhard Schussek <bschussek@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Puli\Repository;

use Puli\Repository\Api\ResourceRepository;
use Puli\Repository\Api\UnsupportedLanguageException;
use Webmozart\Assert\Assert;
use Webmozart\PathUtil\Path;

/**
 * Abstract base for repositories providing tools to avoid code duplication.
 *
 * @since  1.0
 *
 * @author Bernhard Schussek <bschussek@gmail.com>
 * @author Titouan Galopin <galopintitouan@gmail.com>
 */
abstract class AbstractRepository implements ResourceRepository
{
    /**
     * Validate a language is usable to search in repositories.
     *
     * @param string $language
     */
    protected function validateSearchLanguage($language)
    {
        if ('glob' !== $language) {
            throw UnsupportedLanguageException::forLanguage($language);
        }
    }

    /**
     * Sanitize a given path and check its validity.
     *
     * @param string $path
     *
     * @return string
     */
    protected function sanitizePath($path)
    {
        Assert::stringNotEmpty($path, 'The path must be a non-empty string. Got: %s');
        Assert::startsWith($path, '/', 'The path %s is not absolute.');

        return Path::canonicalize($path);
    }
}
