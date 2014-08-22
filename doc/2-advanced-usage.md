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

Create Patterns Automatically
~~~~~~~~~~~~~~~~~~~~~~~~~~~~~

If you use the same kind of pattern often, it's useful to automate the creation
of the `RegExpPattern` instances. Create a `RegExpPatternFactory` which
implements [`PatternFactoryInterface`]:

```php
class RegExpPatternFactory implements PatternFactoryInterface
{
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

The method `acceptsSelector()` returns true whenever a string can be converted
into a `RegExpPattern`. The method `createPattern()` returns the new instance.

If you pass the pattern factory to your repository, it's possible to pass your
custom patterns as strings. Internally, the methods of the pattern factory will
be called to construct new `RegExpPattern` instances automatically:

```php
foreach ($repo->get('~^/webmozart/puli/css/.+\.css$~') as $resource) {
    // ...
}
```

Locate Files by Patterns
~~~~~~~~~~~~~~~~~~~~~~~~

So far, the custom pattern only works for locating files in the repository. If
you also want to use it to locate files on the filesystem, create an
implementation of [`PathFinderInterface`]. As example, look at the code
of [`GlobFinder`]:

```php
class GlobFinder implements PathFinderInterface
{
    // ...

    public function findPaths(PatternInterface $pattern)
    {
        return glob((string) $pattern);
    }
}
```

The `findPaths()` method receives the pattern instance and returns the paths
in the file system which match the pattern.

Similarly, you can implement a `RegExpFinder`. Pass the custom finder to the
[`FilesystemLocator`] backing your resource repository:

```php
$finder = new RegExpFinder();
$locator = new FilesystemLocator(null, null, $finder);
$repo = new ResourceRepository($locator);

// Locate files using regular expressions
$repo->add('/webmozart/puli/css', new RegExpPattern('~^/path/to/css/.+\.css$~'));
```

Now it is possible to locate files on the filesystem with the custom pattern.
As last step, let's use the custom pattern factory and the custom finder
together:

```php
$patternFactory = new RegExpPatternFactory();
$finder = new RegExpFinder();
$locator = new FilesystemLocator(null, $patternFactory, $finder);
$repo = new ResourceRepository($locator, $patternFactory);

// The RegExpPattern is created automatically now
$repo->add('/webmozart/puli/css', '~^/path/to/css/.+\.css$~');
```

[Basic Usage]: 1-basic-usage.md
[`PatternInterface`]: ../src/Pattern/PatternInterface.php
[`PatternFactoryInterface`]: ../src/Pattern/PatternFactoryInterface.php
[`GlobPattern`]: ../src/Pattern/GlobPattern.php
[`GlobPatternFactory`]: ../src/Pattern/GlobPatternFactory.php
[`FilesystemLocator`]: ../src/Filesystem/FilesystemLocator.php
[`PathFinderInterface`]: ../src/Filesystem/PathFinder/PathFinderInterface.php
[`GlobFinder`]: ../src/Filesystem/PathFinder/GlobFinder.php
[`preg_match()`]: http://php.net/manual/en/function.preg_match.php
