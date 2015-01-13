<?php

/*
 * This file is part of the puli/repository package.
 *
 * (c) Bernhard Schussek <bschussek@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Puli\Repository\Assert;

use BadMethodCallException;
use InvalidArgumentException;
use Traversable;

/**
 * Domain-specific assertions.
 *
 * @method static void nullOrString($value, $message = null)
 * @method static void nullOrBoolean($value, $message = null)
 * @method static void nullOrScalar($value, $message = null)
 * @method static void nullOrIsArray($value, $message = null)
 * @method static void nullOrIsTraversable($value, $message = null)
 * @method static void nullOrIsInstanceOf($value, $class, $message = null)
 * @method static void nullOrNotEmpty($value, $message = null)
 * @method static void nullOrTrue($value, $message = null)
 * @method static void nullOrNotEq($value, $message = null)
 * @method static void nullOrOneOf($value, $choices, $message = null)
 * @method static void nullOrContains($value, $part, $message = null)
 * @method static void nullOrStartsWith($value, $prefix, $message = null)
 * @method static void nullOrStartsWithLetter($value, $message = null)
 * @method static void nullOrRegex($value, $pattern, $message = null)
 * @method static void nullOrAlnum($value, $message = null)
 * @method static void nullOrFileExists($value, $message = null)
 * @method static void nullOrFile($value, $message = null)
 * @method static void nullOrDirectory($value, $message = null)
 * @method static void nullOrPath($value, $message = null)
 * @method static void nullOrGlob($value, $message = null)
 * @method static void allString($values, $message = null)
 * @method static void allBoolean($values, $message = null)
 * @method static void allScalar($values, $message = null)
 * @method static void allIsArray($values, $message = null)
 * @method static void allIsTraversable($values, $message = null)
 * @method static void allIsInstanceOf($values, $class, $message = null)
 * @method static void allNotEmpty($values, $message = null)
 * @method static void allTrue($values, $message = null)
 * @method static void allNotEq($values, $message = null)
 * @method static void allOneOf($values, $choices, $message = null)
 * @method static void allContains($values, $part, $message = null)
 * @method static void allStartsWith($values, $prefix, $message = null)
 * @method static void allStartsWithLetter($values, $message = null)
 * @method static void allRegex($values, $pattern, $message = null)
 * @method static void allAlnum($values, $message = null)
 * @method static void allFileExists($values, $message = null)
 * @method static void allFile($values, $message = null)
 * @method static void allDirectory($values, $message = null)
 * @method static void allPath($values, $message = null)
 * @method static void allGlob($values, $message = null)
 *
 * @since  1.0
 * @author Bernhard Schussek <bschussek@gmail.com>
 */
class Assert
{
    public static function string($value, $message = '')
    {
        if (!is_string($value)) {
            throw new InvalidArgumentException(sprintf(
                $message ?: 'Expected a string. Got: %s',
                is_object($value) ? get_class($value) : gettype($value)
            ));
        }
    }

    public static function boolean($value, $message = '')
    {
        if (!is_bool($value)) {
            throw new InvalidArgumentException(sprintf(
                $message ?: 'Expected a boolean. Got: %s',
                is_object($value) ? get_class($value) : gettype($value)
            ));
        }
    }

    public static function scalar($value, $message = '')
    {
        if (!is_scalar($value)) {
            throw new InvalidArgumentException(sprintf(
                $message ?: 'Expected a scalar. Got: %s',
                is_object($value) ? get_class($value) : gettype($value)
            ));
        }
    }

    public static function isArray($value, $message = '')
    {
        if (!is_array($value)) {
            throw new InvalidArgumentException(sprintf(
                $message ?: 'Expected an array. Got: %s',
                is_object($value) ? get_class($value) : gettype($value)
            ));
        }
    }

    public static function isTraversable($value, $message = '')
    {
        if (!is_array($value) && !($value instanceof Traversable)) {
            throw new InvalidArgumentException(sprintf(
                $message ?: 'Expected a traversable. Got: %s',
                is_object($value) ? get_class($value) : gettype($value)
            ));
        }
    }

    public static function isInstanceOf($value, $class, $message = '')
    {
        if (!($value instanceof $class)) {
            throw new InvalidArgumentException(sprintf(
                $message ?: 'Expected an instance of %s. Got: %s',
                $class,
                is_object($value) ? get_class($value) : gettype($value)
            ));
        }
    }

    public static function notEmpty($value, $message = '')
    {
        if (empty($value)) {
            throw new InvalidArgumentException(
                $message ?: 'Expected a non-empty value.'
            );
        }
    }

    public static function true($value, $message = '')
    {
        if (true !== $value) {
            throw new InvalidArgumentException(
                $message ?: 'Expected a value to be true.'
            );
        }
    }

    public static function notEq($value, $value2, $message = '')
    {
        if ($value2 === $value) {
            throw new InvalidArgumentException(sprintf(
                $message ?: 'Expected a different value than %s.',
                self::toString($value)
            ));
        }
    }

    public static function oneOf($value, array $choices, $message = '')
    {
        if (!in_array($value, $choices, true)) {
            throw new InvalidArgumentException(sprintf(
                $message ?: 'Expected one of: %2$s. Got: %s',
                self::toString($value),
                implode(', ', array_map(array(__CLASS__, 'toString'), $choices))
            ));
        }
    }

    public static function contains($value, $part, $message = '')
    {
        if (false === strpos($value, $part)) {
            throw new InvalidArgumentException(sprintf(
                $message ?: 'Expected a value to contain %2$s. Got: %s',
                self::toString($value),
                self::toString($part)
            ));
        }
    }

    public static function startsWith($value, $prefix, $message = '')
    {
        if (0 !== strpos($value, $prefix)) {
            throw new InvalidArgumentException(sprintf(
                $message ?: 'Expected a value to start with %2$s. Got: %s',
                self::toString($value),
                self::toString($prefix)
            ));
        }
    }

    public static function startsWithLetter($value, $message = '')
    {
        $valid = isset($value[0]);

        if ($valid) {
            $locale = setlocale(LC_CTYPE, 0);
            setlocale(LC_CTYPE, 'C');
            $valid = ctype_alpha($value[0]);
            setlocale(LC_CTYPE, $locale);
        }

        if (!$valid) {
            throw new InvalidArgumentException(sprintf(
                $message ?: 'Expected a value to start with a letter. Got: %s',
                self::toString($value)
            ));
        }
    }

    public static function regex($value, $pattern, $message = '')
    {
        if (!preg_match($pattern, $value)) {
            throw new InvalidArgumentException(sprintf(
                $message ?: 'The value %s does not match the expected pattern.',
                self::toString($value)
            ));
        }
    }

    public static function alnum($value, $message = '')
    {
        $locale = setlocale(LC_CTYPE, 0);
        setlocale(LC_CTYPE, 'C');
        $valid = !ctype_alnum($value);
        setlocale(LC_CTYPE, $locale);

        if ($valid) {
            throw new InvalidArgumentException(sprintf(
                $message ?: 'Expected a value to contain characters and digits only. Got: %s',
                self::toString($value)
            ));
        }
    }

    public static function fileExists($value, $message = '')
    {
        self::string($value);

        if (!file_exists($value)) {
            throw new InvalidArgumentException(sprintf(
                $message ?: 'The file %s does not exist.',
                $value
            ));
        }
    }

    public static function file($value, $message = '')
    {
        self::fileExists($value, $message);

        if (!is_file($value)) {
            throw new InvalidArgumentException(sprintf(
                $message ?: 'The path %s is not a file.',
                $value
            ));
        }
    }

    public static function directory($value, $message = '')
    {
        self::fileExists($value, $message);

        if (!is_dir($value)) {
            throw new InvalidArgumentException(sprintf(
                $message ?: 'The path %s is no directory.',
                $value
            ));
        }
    }

    public static function path($path)
    {
        self::string($path, 'The path must be a string. Got: %s');
        self::notEmpty($path, 'The path must not be empty.');
        self::startsWith($path, '/', 'The path %2$s is not absolute.');
    }

    public static function glob($glob)
    {
        self::string($glob, 'The glob must be a string. Got: %s');
        self::notEmpty($glob, 'The glob must not be empty.');
        self::startsWith($glob, '/', 'The glob %2$s is not absolute.');
    }

    public static function toString($value)
    {
        if (null === $value) {
            return 'null';
        }

        if (true === $value) {
            return 'true';
        }

        if (false === $value) {
            return 'false';
        }

        if (is_object($value)) {
            return get_class($value).' object';
        }

        if (is_resource($value)) {
            return 'resource';
        }

        if (is_string($value)) {
            return '"'.$value.'"';
        }

        return (string) $value;
    }

    public static function __callStatic($name, $arguments)
    {
        if ('nullOr' === substr($name, 0, 6)) {
            if (null !== $arguments[0]) {
                $method = lcfirst(substr($name, 6));
                call_user_func_array(array('static', $method), $arguments);
            }

            return;
        }

        if ('all' === substr($name, 0, 3)) {
            self::isTraversable($arguments[0]);

            $method = lcfirst(substr($name, 3));
            $args = $arguments;

            foreach ($arguments[0] as $entry) {
                $args[0] = $entry;

                call_user_func_array(array('static', $method), $args);
            }

            return;
        }

        throw new BadMethodCallException('No such method: '.$name);
    }

    private function __construct()
    {
    }
}
