Locating Files with Puli
========================

[![Build Status](https://travis-ci.org/webmozart/puli.png?branch=master)](https://travis-ci.org/webmozart/puli)
[![Scrutinizer Quality Score](https://scrutinizer-ci.com/g/webmozart/puli/badges/quality-score.png?s=f1fbf1884aed7f896c18fc237d3eed5823ac85eb)](https://scrutinizer-ci.com/g/webmozart/puli/)
[![SensioLabsInsight](https://insight.sensiolabs.com/projects/728198dc-dc0f-4bab-b5c0-c0b4e2a55bce/mini.png)](https://insight.sensiolabs.com/projects/728198dc-dc0f-4bab-b5c0-c0b4e2a55bce)
[![Latest Stable Version](https://poser.pugx.org/webmozart/puli/v/stable.png)](https://packagist.org/packages/webmozart/puli)
[![Total Downloads](https://poser.pugx.org/webmozart/puli/downloads.png)](https://packagist.org/packages/webmozart/puli)
[![Dependency Status](https://www.versioneye.com/php/webmozart:puli/1.0.0/badge.png)](https://www.versioneye.com/php/webmozart:puli/1.0.0)

PHP >= 5.3.9

Puli returns the absolute file paths of the files (*resources*) in your PHP
project through a unified naming system. Essentially, Puli lets you use a
*resource locator* to access files like this:

```php
echo $locator->get('/webmozart/puli/css/style.css')->getPath();
// => /path/to/resources/assets/css/style.css
```

This document teaches you how to use Puli in practice.

Installation
------------

You can install Puli with Composer:

```json
{
    "require": {
        "webmozart/puli": "~1.0@alpha"
    }
}
```

Run `composer install` or `composer update` and you're ready to start.

Tool Integration
----------------

Tool        | Description                                                                               | Version
----------- | ----------------------------------------------------------------------------------------- | ---------------
Composer    | The [Puli plugin for Composer] builds resource locators from composer.json definitions.   | 1.0.0-alpha1
Twig        | The [Puli extension for Twig] lets you access templates via Puli paths.                   | 1.0.0-dev


Repository Management
---------------------

Puli manages files in a *repository*, where you map them to a path:

```php
use Webmozart\Puli\Repository\ResourceRepository;

$repo = new ResourceRepository();
$repo->add('/webmozart/puli', '/path/to/resources/assets/*');
$repo->add('/webmozart/puli/trans', '/path/to/resources/trans');
```

The method `add()` works very much like copying on your local file system. The
only difference is that no file is ever really moved on your disk.

You can locate the added files using the `get()` method:

```php
echo $repo->get('/webmozart/puli/css/style.css')->getPath();
// => /path/to/resources/assets/css/style.css

echo $repo->get('/webmozart/puli/trans/en.xlf')->getPath();
// => /path/to/resources/trans/en.xlf
```

The `get()` method accepts either the path to the resource, a glob pattern or an
array containing multiple paths or patterns. If you pass a pattern or an array,
the method will always return an array as well.

```php
foreach ($repo->get('/webmozart/puli/*') as $resource) {
    echo $resource->getRepositoryPath();
}

// => /webmozart/puli/css
// => /webmozart/puli/trans
```

You can remove resources from the repository with the `remove()` method:

```php
$repo->remove('/webmozart/puli/css');
```

Resource Locators
-----------------

Building and configuring a repository is expensive and should not be done on
every request. For this reason, Puli allows to dump resource locators that are
optimized for retrieving resources. Resource locators must implement the
interface [`ResourceLocatorInterface`], which provides a subset of the
methods available in the resource repository. Naturally, resource locators are
frozen and cannot be modified.

Currently, Puli only provides one locator implementation: [`PhpResourceLocatorDumper`].
This locator caches the paths to the resources in your repository in PHP files.
These files are usually stored in the cache directory of your application. Pass
the path to this cache directory when you call the `dumpLocator()` method:

```php
use Webmozart\Puli\LocatorDumper\PhpResourceLocatorDumper;

$dumper = new PhpResourceLocatorDumper();
$dumper->dumpLocator($repo, '/path/to/cache');
```

Then create a [`PhpResourceLocator`] and pass the path to the directory where
you dumped the PHP files. The locator lets you then access the resources in the
same way as the repository does:

```php
use Webmozart\Puli\Locator\PhpResourceLocator;

$locator = new PhpResourceLocator('/path/to/cache');

echo $locator->get('/webmozart/puli/css/style.css')->getPath();
// => /path/to/resources/assets/css/style.css

echo $locator->get('/webmozart/puli/trans/en.xlf')->getPath();
// => /path/to/resources/trans/en.xlf
```

URI Locators
------------

Puli allows to use multiple resource locators at the same time through the
[`UriLocator`]. You can register multiple [`ResourceLocatorInterface`]
instances for different URI schemes. Then you can use the [`UriLocator`]
like a regular resource locator, except you need to pass URIs instead of
simple paths. An example tells a thousand stories:

```php
use Webmozart\Puli\Locator\PhpResourceLocator;
use Webmozart\Puli\Locator\UriLocator;

$locator = new UriLocator();
$locator->register('resource', new PhpResourceLocator('/cache/resource'));
$locator->register('namespace', new PhpResourceLocator('/cache/namespace'));

echo $locator->get('resource:///webmozart/puli/css/style.css')->getPath();
// => /path/to/resources/assets/css/style.css

echo $locator->get('namespace:///Webmozart/Puli/Puli.php')->getPath();
// => /path/to/webmozart/puli/src/Puli.php
```

In this example, the URI locator routes all requests for URIs with the
protocol "resource://" to one resource locator and requests for URIs with the
protocol "namespace://" to the other locator.

To improve performance in requests where you don't access either of the
protocols, you can also register callbacks that create the resource locators:

```php
$locator->register('resource', function () {
    return new PhpResourceLocator('/cache/resource')
});
```

Streams
-------

Puli supports a stream wrapper that lets you access the contents of the
repository transparently through PHP's file functions. To register the wrapper,
call the `register()` method in [`ResourceStreamWrapper`] and pass a
configured [`UriLocator`]:

```php
use Webmozart\Puli\Locator\UriLocator;
use Webmozart\Puli\StreamWrapper\ResourceStreamWrapper;

$locator = new UriLocator();
$locator->register('resource', $repository);

ResourceStreamWrapper::register($locator);
```

You can now use regular PHP functions to access the files and directories in
the repository.

```php
$contents = file_get_contents('resource:///webmozart/puli/css/style.css');

$entries = scandir('resource:///webmozart/puli');
```

Even better: If you register the stream wrapper, you can use Puli resources
with all frameworks and libraries that use PHP's file functions under the hood.

Resources
---------

The `get()` method returns one or more [`ResourceInterface`] instances. This
interface lets you access the name, the repository path and the real file path
of the resource:

```php
$resource = $repo->get('/webmozart/puli/css');

echo $resource->getName();
// => css

echo $resource->getRepositoryPath();
// => /webmozart/puli/css

echo $resource->getPath();
// => /path/to/resources/assets/css
```

The method `getName()` will always return the name of the resource in the
repository. If you want to retrieve the name of the resource on the filesystem,
use [`basename()`] instead:

```php
$repo->add('/webmozart/puli', '/path/to/resources/assets');

$resource = $repo->get('/webmozart/puli');

echo $resource->getName();
// => puli

echo basename($resource->getPath());
// => assets
```

Directories
-----------

Directory resources implement the additional interface
[`DirectoryResourceInterface`]. This way you can easily distinguish directories
from files:

```php
use Webmozart\Puli\Resource\DirectoryResourceInterface;

$resource = $repo->get('/webmozart/puli/css');

if ($resource instanceof DirectoryResourceInterface) {
    // ...
}
```

Directories are traversable and countable:

```php
$directory = $repo->get('/webmozart/puli/css');

echo count($directory);
// => 2

foreach ($directory as $resource) {
    // ...
}
```

You can access the contents of a directory with the methods `get()`,
`contains()` and `all()` or use its `ArrayAccess` interface:

```php
$resource = $directory->get('style.css');
$resource = $directory['style.css'];

if ($directory->contains('style.css')) {
    // ...
}

if (isset($directory['style.css'])) {
    // ...
}

$resources = $directory->all();
```

Direct modifications of [`DirectoryResourceInterface`] instances are not
allowed. You should use the methods provided by [`ResourceRepositoryInterface`]
instead.

Overriding Files and Directories
--------------------------------

Puli lets you override files and directories without losing the original paths.
This is very useful if you want to remember a cascade of files in order to merge
them later on. The method `getAlternativePaths()` returns all paths that were
registered for a resource, in the order of their registration:

```php
$repo->add('/webmozart/puli/config', '/path/to/vendor/webmozart/puli/config');
$repo->add('/webmozart/puli/config', '/path/to/app/config');

$resource = $repo->get('/webmozart/puli/config/config.yml');

echo $resource->getPath();
// => /path/to/app/config/config.yml

print_r($resource->getAlternativePaths());
// Array
// (
//     [0] => /path/to/vendor/webmozart/puli/config/config.yml
//     [1] => /path/to/app/config/config.yml
// )
```

Tagging
-------

Resources managed by Puli can be tagged. This is useful for marking resources
that support specific features. For example, you can tag all XLIFF translation
files that can be consumed by a class `Acme\Translator`:

```php
$repo->tag('/webmozart/puli/translations/*.xlf', 'acme/translator/xlf');
```

You can remove one or all tags from a resource using the `untag()` method:

```php
// Remove the tag "acme/translator/xlf"
$repo->untag('/webmozart/puli/translations/*.xlf', 'acme/translator/xlf');

// Remove all tags
$repo->untag('/webmozart/puli/translations/*.xlf');
```

You can get all files that bear a specific tag with the `getByTag()` method:

```php
$resources = $repo->getByTag('acme/translator/xlf');
```

You can also read all tags that have been registered in the repository:

```php
$tags = $repo->getTags();
```

This method will return an array of strings, namely the tags that have been
registered.

Automated Resource Discovery
----------------------------

Tagging can be used to implement classes that autonomously discover the
resources they need. For example, the `Acme\Translator` class mentioned before
can provide a `discoverResources()` method which extracts all resources marked
with the "acme/translator/xlf" tag from the repository:

```php
namespace Acme;

use Webmozart\Puli\Locator\ResourceLocatorInterface;

class Translator
{
    // ...

    public function discoverResources(ResourceLocatorInterface $locator)
    {
        foreach ($locator->getByTag('acme/translator/xlf') as $resource) {
            // register $resource->getPath()...
        }
    }
}
```

Adding the tagged files to the translator becomes as easy as passing the
repository or the resource locator:

```php
use Acme\Translator;

$translator = new Translator();
$translator->discoverResources($repo);
```

Puli provides an interface [`ResourceDiscoveringInterface`] for marking such
classes. Dependency Injection Containers can rely on this interface to inject
the resource locator automatically.

```php
namespace Acme;

use Webmozart\Puli\ResourceDiscoveringInterface;

class Translator implements ResourceDiscoveringInterface
{
    // ...
}
```

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

[Composer plugin]: https://github.com/webmozart/composer-puli-plugin
[Puli plugin for Composer]: https://github.com/webmozart/composer-puli-plugin
[Puli extension for Twig]: https://github.com/webmozart/twig-puli-extension
[`ResourceDiscoveringInterface`]: src/ResourceDiscoveringInterface.php
[`ResourceRepositoryInterface`]: src/Repository/ResourceRepositoryInterface.php
[`ResourceInterface`]: src/Resource/ResourceInterface.php
[`DirectoryResourceInterface`]: src/Resource/DirectoryResourceInterface.php
[`ResourceLocatorInterface`]: src/Locator/ResourceLocatorInterface.php
[`PhpResourceLocator`]: src/Locator/PhpResourceLocator.php
[`PhpResourceLocatorDumper`]: src/LocatorDumper/PhpResourceLocatorDumper.php
[`PatternInterface`]: src/Pattern/PatternInterface.php
[`PatternFactoryInterface`]: src/Pattern/PatternFactoryInterface.php
[`GlobPattern`]: src/Pattern/GlobPattern.php
[`PatternLocatorInterface`]: src/PatternLocator/PatternLocatorInterface.php
[`GlobPatternLocator`]: src/PatternLocator/GlobPatternLocator.php
[`ResourceStreamWrapper`]: src/StreamWrapper/ResourceStreamWrapper.php
[`UriLocator`]: src/Locator/UriLocator.php
[`basename()`]: http://php.net/manual/en/function.basename.php
[`preg_match()`]: http://php.net/manual/en/function.preg_match.php
