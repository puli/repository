Basic Usage
===========

Welcome to Puli! Enjoy this guide to learn about the basics with Puli.

Table of Contents
-----------------

1. [Repository Management](#repository-management)
2. [Resource Locators](#resource-locators)
3. [URI Locators](#uri-locators)
4. [Streams](#streams)
5. [Resources](#resources)
6. [Directories](#directories)
7. [Resource Collections](#resource-collections)
8. [Overriding Files and Directories](#overriding-files-and-directories)
9. [Tagging](#tagging)
10. [Automated Resource Discovery](#automated-resource-discovery)

Repository Management
---------------------

Puli manages files in a *repository*, where you map them to a path:

```php
use Puli\ResourceRepository;

$repo = new ResourceRepository();
$repo->add('/', '/path/to/resources/assets/*');
$repo->add('/trans', '/path/to/resources/trans');
```

The method `add()` works very much like copying on your local file system. The
only difference is that no file is ever really moved on your disk.

You can locate the added files using the `get()` method:

```php
echo $repo->get('/css/style.css')->getLocalPath();
// => /path/to/resources/assets/css/style.css

echo $repo->get('/trans/en.xlf')->getLocalPath();
// => /path/to/resources/trans/en.xlf
```

The `get()` method accepts the path of a resource and returns a 
[`ResourceInterface`]. If you want to retrieve multiple resources, use `find()`.
This method accepts a glob pattern and returns a [`ResourceCollectionInterface`].

```php
foreach ($repo->find('/*')->getPaths() as $path) {
    echo $path;
}

// => /css
// => /trans
```

You can remove resources from the repository with the `remove()` method:

```php
$repo->remove('/css');
```

Read-Only Repositories
----------------------

Building and configuring a repository is expensive and should not be done on
every request. For this reason, Puli supports repositories that are optimized 
for reading and cannot be modified.

A very simple example is the [`PhpCacheRepository`]. This repository reads the
resource paths from a set of PHP files. These files are created with the
`dumpRepository()` method:

```php
use Puli\Filesystem\PhpCacheRepository;
use Puli\ResourceRepository;

$repo = new ResourceRepository(),
// configure repository...

PhpCacheRepository::dumpRepository($repo, '/path/to/cache');
```

Then create a [`PhpCacheRepository`] and pass the path to the directory where
you dumped the PHP files:

```php
$repo = new PhpCacheRepository('/path/to/cache');

echo $repo->get('/css/style.css')->getLocalPath();
// => /path/to/resources/assets/css/style.css

echo $repo->get('/trans/en.xlf')->getLocalPath();
// => /path/to/resources/trans/en.xlf
```

The following repositories are provided by Puli:

Repository               | Description                            | Writable
------------------------ | -------------------------------------- | --------
[`ResourceRepository`]   | Manages resources in memory.           | Yes
[`PhpCacheRepository`]   | Reads resources from dumped PHP files. | No
[`FilesystemRepository`] | Reads resources from the filesystem.   | No

URI Repositories
----------------

Puli allows to use multiple repositories at the same time through the
[`UriRepository`]. You can register multiple [`ResourceRepositoryInterface`]
instances for different URI schemes. Then you can use the [`UriRepository`]
like a regular repository, except that you pass URIs instead of paths.
An example tells a thousand stories:

```php
use Puli\Filesystem\PhpCacheRepository;
use Puli\Uri\UriRepository;

$locator = new UriRepository();
$locator->register('resource', new PhpCacheRepository('/cache/resource'));
$locator->register('namespace', new PhpCacheRepository('/cache/namespace'));

echo $locator->get('resource:///css/style.css')->getLocalPath();
// => /path/to/resources/assets/css/style.css

echo $locator->get('namespace:///Webmozart/Puli/Puli.php')->getLocalPath();
// => /path/to/src/Puli.php
```

In this example, the URI locator routes all requests for URIs with the
protocol "resource://" to one resource locator and requests for URIs with the
protocol "namespace://" to the other locator.

To improve performance in requests where you don't access all of the protocols, 
you can also register callbacks that create the repositories on demand:

```php
$locator->register('resource', function () {
    return new PhpCacheRepository('/cache/resource')
});
```

Stream Wrapper
--------------

Puli supports a stream wrapper that lets you access the contents of the
repository transparently through PHP's file functions. To register the wrapper,
call the `register()` method in [`ResourceStreamWrapper`] and pass a
configured [`UriRepository`]:

```php
use Puli\Locator\UriRepository;
use Puli\StreamWrapper\ResourceStreamWrapper;

$locator = new UriRepository();
$locator->register('resource', $repository);

ResourceStreamWrapper::register($locator);
```

You can now use regular PHP functions to access the files and directories in
the repository.

```php
$contents = file_get_contents('resource:///css/style.css');

$entries = scandir('resource:///css');
```

Even better: If you register the stream wrapper, you can use Puli resources
with all frameworks and libraries that use PHP's file functions under the hood.

Resources
---------

The `get()` method returns one or more [`ResourceInterface`] instances. This
interface lets you access the name and the repository path of the resource:

```php
$resource = $repo->get('/css');

echo $resource->getName();
// => css

echo $resource->getPath();
// => /css
```

Resources don't necessarily have to be located on the file system. But those
that do implement [`LocalResourceInterface`], which lets you access the
filesystem path with `getLocalPath()`:

```php
$resource = $repo->get('/css/style.css');

echo $resource->getLocalPath();
// => /path/to/resources/assets/css/style.css
```

Directories
-----------

Directory resources implement the additional interface
[`DirectoryResourceInterface`]. This way you can easily distinguish directories
from files:

```php
use Puli\Resource\DirectoryResourceInterface;

$resource = $repo->get('/css');

if ($resource instanceof DirectoryResourceInterface) {
    // ...
}
```

You can access the contents of a directory with the methods `get()`,
`contains()` and `listEntries()`:

```php
$resource = $directory->get('style.css');

if ($directory->contains('style.css')) {
    // ...
}

foreach ($directory->listEntries() as $name => $resource) {
    // ...
}
```

Resource Collections
--------------------

When you fetch multiple resources from the repository, they will be returned
in a [`ResourceCollectionInterface`] instance. Resource collections offer
convenience methods for accessing the names and the paths of the contained 
resources at once:

```php
$resources = $locator->get('/css/*.css');

print_r($resources->getNames());
// Array
// (
//     [0] => reset.css
//     [1] => style.css
// )

print_r($resources->getPaths());
// Array
// (
//     [0] => /css/reset.css
//     [1] => /css/style.css
// )
```

Resource collections are traversable, countable and support `ArrayAccess`.
When you still need the collection as array, call `toArray()`:

```php
$array = $resources->toArray();
```

Overriding Files and Directories
--------------------------------

Puli lets you override files and directories without losing the original paths.
This is very useful if you want to remember a cascade of files in order to merge
them later on. The method `getAllLocalPaths()` returns all paths that were
registered for a resource, in the order of their registration:

```php
$repo->add('/config', '/path/to/vendor/webmozart/puli/config');
$repo->add('/config', '/path/to/app/config');

$resource = $repo->get('/config/config.yml');

echo $resource->getLocalPath();
// => /path/to/app/config/config.yml

print_r($resource->getAllLocalPaths());
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
$repo->tag('/translations/*.xlf', 'acme/translator/xlf');
```

You can remove one or all tags from a resource using the `untag()` method:

```php
// Remove the tag "acme/translator/xlf"
$repo->untag('/translations/*.xlf', 'acme/translator/xlf');

// Remove all tags
$repo->untag('/translations/*.xlf');
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

use Puli\Locator\ResourceRepositoryInterface;

class Translator
{
    // ...

    public function discoverResources(ResourceRepositoryInterface $locator)
    {
        foreach ($locator->getByTag('acme/translator/xlf') as $resource) {
            // register $resource->getLocalPath()...
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

[`ResourceRepositoryInterface`]: ../src/ResourceRepositoryInterface.php
[`ResourceInterface`]: ../src/Resource/ResourceInterface.php
[`LocalResourceInterface`]: ../src/Filesystem/Resource/LocalResourceInterface.php
[`ResourceCollectionInterface`]: ../src/Resource/Collection/ResourceCollectionInterface.php
[`DirectoryResourceInterface`]: ../src/Resource/DirectoryResourceInterface.php
[`FilesystemRepository`]: ../src/Filesystem/FilesystemRepository.php
[`PhpCacheRepository`]: ../src/Filesystem/PhpCacheRepository.php
[`ResourceStreamWrapper`]: ../src/StreamWrapper/ResourceStreamWrapper.php
[`UriRepository`]: ../src/Uri/UriRepository.php
[`basename()`]: http://php.net/manual/en/function.basename.php
