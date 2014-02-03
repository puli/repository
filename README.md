Locating Files with Puli
========================

Puli provides access to the files (*resources*) of your PHP project through a
unified naming system.

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
every request. For this reason, Puli allows to dump *resource locators* that are
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

Each of these tags is an instance of [`TagInterface`]. This interface lets you
access both the tag's name and the resources that currently bear this tag:

```php
echo $tag->getName();
// => acme/translator/xlf

foreach ($tag->getResources() as $resource) {
    // ...
}
```

At last, you can query the tags of an individual resource using the `getTags()`
method in [`ResourceInterface`]:

```
foreach ($resource->getTags() as $tag) {
    echo $tag->getName();
}
```

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

[`ResourceDiscoveringInterface`]: src/ResourceDiscoveringInterface.php
[`ResourceRepositoryInterface`]: src/Repository/ResourceRepositoryInterface.php
[`ResourceInterface`]: src/Resource/ResourceInterface.php
[`DirectoryResourceInterface`]: src/Resource/DirectoryResourceInterface.php
[`ResourceLocatorInterface`]: src/Locator/ResourceLocatorInterface.php
[`PhpResourceLocator`]: src/Locator/PhpResourceLocator.php
[`PhpResourceLocatorDumper`]: src/LocatorDumper/PhpResourceLocatorDumper.php
[`TagInterface`]: src/Tag/TagInterface.php
[`basename()`]: http://php.net/manual/en/function.basename.php
