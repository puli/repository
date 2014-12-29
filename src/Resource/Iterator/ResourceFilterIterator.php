<?php

/*
 * This file is part of the puli/repository package.
 *
 * (c) Bernhard Schussek <bschussek@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Puli\Repository\Resource\Iterator;

use Assert\Assertion;
use FilterIterator;
use Puli\Repository\Api\ResourceIterator;

/**
 * Iterates over a {@link ResourceIterator} and filters out individual entries.
 *
 * You can use the iterator to filter files with a specific extension:
 *
 * ```php
 * $iterator = new ResourceFilterIterator(
 *     new RecursiveResourceIteratorIterator(
 *         new ResourceCollectionIterator($collection),
 *     ),
 *     '.css',
 *     ResourceFilterIterator::MATCH_SUFFIX
 * );
 *
 * foreach ($iterator as $path => $resource) {
 *     // ...
 * }
 * ```
 *
 * See {@link __construct} for more information on the filter options.
 *
 * @since  1.0
 * @author Bernhard Schussek <bschussek@gmail.com>
 */
class ResourceFilterIterator extends FilterIterator implements ResourceIterator
{
    /**
     * Matches the pattern against the resource path.
     */
    const FILTER_BY_PATH = 1;

    /**
     * Matches the pattern against the resource name.
     */
    const FILTER_BY_NAME = 2;

    /**
     * Includes resources if the pattern is a prefix of the matched text.
     */
    const MATCH_PREFIX = 32;

    /**
     * Includes resources if the pattern is a suffix of the matched text.
     */
    const MATCH_SUFFIX = 64;

    /**
     * Includes resources if the matched text satisfies the pattern as regular
     * expression.
     */
    const MATCH_REGEX = 128;

    /**
     * @var string
     */
    private $pattern;

    /**
     * @var int
     */
    private $patternLength;

    /**
     * @var int
     */
    private $mode;

    /**
     * Creates a new iterator.
     *
     * The following constants can be used to configure what to filter by:
     *
     *  * {@link FILTER_BY_PATH}: The pattern is matched against the paths;
     *  * {@link FILTER_BY_NAME}: The pattern is matched against the names.
     *
     * The following constants can be used to configure how to match the
     * selected text:
     *
     *  * {@link MATCH_PREFIX}: Tests whether the pattern is a prefix of the
     *                          matched text;
     *  * {@link MATCH_SUFFIX}: Tests whether the pattern is a suffix of the
     *                          matched text;
     *  * {@link MATCH_REGEX}: Treats the pattern as regular expression.
     *
     * By default, the mode `FILTER_BY_PATH | MATCH_REGEX` is used.
     *
     * @param ResourceIterator $iterator The filtered iterator.
     * @param string           $pattern  The pattern to match.
     * @param int|null         $mode     A bitwise combination of the mode
     *                                   constants.
     */
    public function __construct(ResourceIterator $iterator, $pattern, $mode = null)
    {
        Assertion::string($pattern, 'The pattern must be a string. Got: %2$s');
        Assertion::notEmpty($pattern, 'The pattern must not be empty');

        parent::__construct($iterator);

        if (!($mode & (self::FILTER_BY_PATH | self::FILTER_BY_NAME))) {
            $mode |= self::FILTER_BY_PATH;
        }

        if (!($mode & (self::MATCH_PREFIX | self::MATCH_SUFFIX | self::MATCH_REGEX))) {
            $mode |= self::MATCH_REGEX;
        }

        $this->pattern = $pattern;
        $this->patternLength = strlen($pattern);
        $this->mode = $mode;
    }

    /**
     * Returns whether the current element should be accepted.
     *
     * @return bool Returns `false` if the current element should be filtered out.
     */
    public function accept()
    {
        if ($this->mode & self::FILTER_BY_PATH) {
            $value = $this->getCurrentResource()->getPath();
        } else {
            $value = $this->getCurrentResource()->getName();
        }

        if ($this->mode & self::MATCH_PREFIX) {
            return 0 === strpos($value, $this->pattern);
        } elseif ($this->mode & self::MATCH_SUFFIX) {
            return $this->pattern === substr($value, -$this->patternLength);
        } else {
            return preg_match($this->pattern, $value);
        }
    }

    /**
     * {@inheritdoc}
     */
    public function getCurrentResource()
    {
        return $this->getInnerIterator()->getCurrentResource();
    }
}
