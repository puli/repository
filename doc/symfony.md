Locating Config Files with Puli
===============================

Puli provides a file locator for the [Symfony Config component] that locates
configuration files with a Puli resource locator. With this extension, you can
refer from one configuration file to another via its Puli path:

```yaml
# routing.yml
_acme_demo:
    resource: /acme/demo-bundle/config/routing.yml
```

Installation
------------

To locate configuration files with Puli, create a new [`PuliFileLocator`] and
pass it to your file loaders:

```php
use Webmozart\Puli\Extension\Symfony\Config\PuliFileLocator;
use Symfony\Component\Routing\Loader\YamlFileLoader;

$loader = new YamlFileLoader(new PuliFileLocator($locator));

// Locates the file with Puli's resource locator
$routes = $loader->load('/acme/blog/config/routing.yml');
```

You need to pass Puli's resource locator to the constructor of the
[`PuliFileLocator`]. If you don't know how to create that locator, you can find
more information in Puli's [main documentation].

Limitations
-----------

Due to limitations with Symfony's `FileLocatorInterface`, file references
starting with "../" are not properly supported. Let's load the routes in
"/acme/blog/config/routing.yml" for example:

```php

Chained Locators
----------------

If you want to use the [`PuliFileLocator`] and Symfony's conventional
`FileLocator` side by side, you can use them both by wrapping them into a
[`FileLocatorChain`]:

```php
use Webmozart\Puli\Extension\Symfony\Config\PuliFileLocator;
use Webmozart\Puli\Extension\Symfony\Config\FileLocatorChain;
use Webmozart\Puli\Extension\Symfony\Config\ChainableFileLocator;
use Symfony\Component\Routing\Loader\YamlFileLoader;

$locatorChain = new FileLocatorChain(array(
    new PuliFileLocator($locator),
    // Symfony's FileLocator expects a list of paths
    new ChainableFileLocator(array(__DIR__)),
));

$loader = new YamlFileLoader($locatorChain);

// Loads the file from __DIR__/config/routing.yml
$routes = $loader->load('config/routing.yml');
```

[`ChainableFileLocator`] is a simple extension of Symfony's `FileLocator` that
supports the interface required by the locator chain. Note that this locator
must come *after* the [`PuliFileLocator`] in the chain.

Puli also provides a chainable version of the file locator bundled with the
[Symfony HttpKernel component]: [`ChainableKernelFileLocator`]. Use that
locator if you want to load configuration files from Symfony bundles:

```php
use Webmozart\Puli\Extension\Symfony\Config\PuliFileLocator;
use Webmozart\Puli\Extension\Symfony\Config\FileLocatorChain;
use Webmozart\Puli\Extension\Symfony\Config\ChainableFileLocator;
use Webmozart\Puli\Extension\Symfony\HttpKernel\ChainableKernelFileLocator;

$locatorChain = new FileLocatorChain(array(
    new PuliFileLocator($locator),
    new ChainableKernelFileLocator($httpKernel),
    new ChainableFileLocator(array(__DIR__)),
));

$loader = new YamlUserLoader($locatorChain);

// Loads the file from AcmeBlogBundle
$routes = $loader->load('@AcmeBlogBundle/Resources/config/routing.yml');
```

Take care again that the [`ChainableFileLocator`] comes last in the chain.

[Symfony Config component]: http://symfony.com/doc/current/components/config/introduction.html
[Symfony HttpKernel component]: http://symfony.com/doc/current/components/http_kernel/introduction.html
[main documentation]: ../README.md
[`PuliFileLocator`]: ../src/Extension/Symfony/Config/PuliFileLocator.php
[`FileLocatorChain`]: ../src/Extension/Symfony/Config/FileLocatorChain.php
[`ChainableFileLocator`]: ../src/Extension/Symfony/Config/ChainableFileLocator.php
[`ChainableKernelFileLocator`]: ../src/Extension/Symfony/HttpKernel/ChainableKernelFileLocator.php
