Advanced Usage
==============

Are you fully confident with Puli's [Basic Usage] and want to learn more? Then
read this guide, which introduces you to advanced functionality of Puli.

Table of Contents
-----------------

1. [Custom File Patterns](#custom-file-patterns)

Custom File Patterns
--------------------

By default, you can use Glob patterns to locate files both in the repository and
on your filesystem:

```php
// Glob the repository
foreach ($repo->get('/webmozart/puli/css/*.css') as $resource) {
    // ...
}

// Glob the filesystem
$repo->add('/webmozart/puli/css', '/path/to/resources/css/*.css');
```

If you want to use other patterns than Glob, you can create custom
implementations of [`PatternInterface`]. As an example, look at the code of
[`GlobPattern`]:

```php
class GlobPattern implements PatternInterface
{
    private $pattern;

    private $staticPrefix;

    private $regExp;

    public function __construct($pattern)
    {
        $this->pattern = $pattern;
        $this->regExp = '~^'.str_replace('\*', '[^/]+', preg_quote($pattern, '~')).'$~';

        if (false !== ($pos = strpos($pattern, '*'))) {
            $this->staticPrefix = substr($pattern, 0, $pos);
        } else {
            $this->staticPrefix = $pattern;
        }
    }

    public function getStaticPrefix()
    {
        return $this->staticPrefix;
    }

    public function getRegularExpression()
    {
        return $this->regExp;
    }

    public function __toString()
    {
        return $this->pattern;
    }
}
```

The method `getRegularExpression()` returns the pattern converted to a regular
expression. The method `getStaticPrefix()` returns the prefix of the path that
never changes. This is used to reduce the number of internal [`preg_match()`]
calls.

In order to use custom patterns, pass the [`PatternInterface`] instance wherever
a Glob pattern is accepted. For example, if you implement a class
`RegExpPattern`:

```php
foreach ($repo->get(new RegExpPattern('~^/webmozart/puli/css/.+\.css$~')) as $resource) {
    // ...
}
```

So far, the custom pattern only works for locating files in the repository. If
you also want to use it to locate files on the filesystem, create an
implementation of [`PatternLocatorInterface`]. As example, look at the code
of [`GlobPatternLocator`]:

```php
class GlobPatternLocator implements PatternLocatorInterface
{
    // ...

    public function locatePaths(PatternInterface $pattern)
    {
        return glob((string) $pattern);
    }
}
```

The `locatePaths()` method receives the pattern instance and returns the paths
in the file system which match the pattern.

To use the locator, create an implementation of [`PatternFactoryInterface`] and
return the locator from the `createPatternLocator()` method. A convenient
solution is to let the locator itself implement this interface. If we assume
again that you implemented a `RegExpPatternLocator`, you can extend the
implementation like this:

```php
class RegExpPatternLocator implements PatternLocatorInterface, PatternFactoryInterface
{
    // ...

    public function createPatternLocator()
    {
        return $this;
    }
}
```

Pass the factory to the constructor of the repository or the resource locator:

```php
$repo = new ResourceRepository(new RegExpPatternLocator());

// Locate files using regular expressions
$repo->add('/webmozart/puli/css', new RegExpPattern('~^/path/to/css/.+\.css$~'));
```

If you try to implement the above code snippets, you will notice that the
[`PatternFactoryInterface`] requires to implement two more methods, namely
`acceptsSelector()` and `createPattern()`. These methods help to automatically
create [`PatternInterface`] instances from the string selectors passed to
`add()`, `get()` and similar methods. You could implement the methods like this:

```php
class RegExpPatternLocator implements PatternLocatorInterface, PatternFactoryInterface
{
    // ...

    public function acceptsSelector($selector)
    {
        return '~' === $selector[0];
    }

    public function createPattern($selector)
    {
        new RegExpPattern($selector);
    }
}
```

With these additions, it's possible to pass custom patterns as strings.
Internally, the methods of the pattern factory will be called to construct a new
`RegExpPattern` instance automatically:

```php
$repo->add('/webmozart/puli/css', '~^/path/to/css/.+\.css$~');
```

[Basic Usage]: 1-basic-usage.md
[`PatternInterface`]: ../src/Pattern/PatternInterface.php
[`PatternFactoryInterface`]: ../src/Pattern/PatternFactoryInterface.php
[`GlobPattern`]: ../src/Pattern/GlobPattern.php
[`PatternLocatorInterface`]: ../src/PatternLocator/PatternLocatorInterface.php
[`GlobPatternLocator`]: ../src/PatternLocator/GlobPatternLocator.php
[`preg_match()`]: http://php.net/manual/en/function.preg_match.php
