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
use Puli\Extension\Symfony\Config\PuliFileLocator;
use Symfony\Component\Routing\Loader\YamlFileLoader;

$loader = new YamlFileLoader(new PuliFileLocator($repo));

// Locates the file from Puli's repository
$routes = $loader->load('/acme/blog/config/routing.yml');
```

You need to pass Puli's resource repository to the constructor of the
[`PuliFileLocator`]. If you don't know how to create that repository, you can 
find more information in Puli's [main documentation].

Chained Locators
----------------

If you want to use the [`PuliFileLocator`] and Symfony's conventional
`FileLocator` side by side, you can use them both by wrapping them into a
[`FileLocatorChain`]:

```php
use Puli\Extension\Symfony\Config\PuliFileLocator;
use Puli\Extension\Symfony\Config\FileLocatorChain;
use Puli\Extension\Symfony\Config\ChainableFileLocator;
use Symfony\Component\Routing\Loader\YamlFileLoader;

$locatorChain = new FileLocatorChain(array(
    new PuliFileLocator($repo),
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
use Puli\Extension\Symfony\Config\PuliFileLocator;
use Puli\Extension\Symfony\Config\FileLocatorChain;
use Puli\Extension\Symfony\Config\ChainableFileLocator;
use Puli\Extension\Symfony\HttpKernel\ChainableKernelFileLocator;

$locatorChain = new FileLocatorChain(array(
    new PuliFileLocator($repo),
    new ChainableKernelFileLocator($httpKernel),
    new ChainableFileLocator(array(__DIR__)),
));

$loader = new YamlUserLoader($locatorChain);

// Loads the file from AcmeBlogBundle
$routes = $loader->load('@AcmeBlogBundle/Resources/config/routing.yml');
```

Take care again that the [`ChainableFileLocator`] comes last in the chain.

Limitations
-----------

Due to limitations with Symfony's `FileLocatorInterface`, relative file
references are not properly supported. Let's load some routes for example:

```php
$routes = $loader->load('/acme/blog/config/routing-dev.yml');
```

Consider that this file contains the following import:

```yaml
# routing-dev.yml
_main:
    resource: routing.yml
```

What happens if we override this file in the Puli repository?

```php
// Load files from /path/to/blog
$repo->add('/acme/blog', '/path/to/blog');

// Override just routing.yml with a custom file
$repo->add('/acme/blog/config/routing.yml', '/path/to/routing.yml');

// Load the routes
$routes = $loader->load('/acme/blog/config/routing-dev.yml');

// Expected: Routes loaded from
//  - /path/to/blog/config/routing-dev.yml
//  - /path/to/routing.yml

// Actual: Routes loaded from
//  - /path/to/blog/config/routing-dev.yml
//  - /path/to/blog/config/routing.yml
```

This is a limitation in Symfony and cannot be worked around. For this
reason, [`PuliFileLocator`] does not support relative file paths.

[Symfony Config component]: http://symfony.com/doc/current/components/config/introduction.html
[Symfony HttpKernel component]: http://symfony.com/doc/current/components/http_kernel/introduction.html
[main documentation]: ../README.md
[`PuliFileLocator`]: ../src/Extension/Symfony/Config/PuliFileLocator.php
[`FileLocatorChain`]: ../src/Extension/Symfony/Config/FileLocatorChain.php
[`ChainableFileLocator`]: ../src/Extension/Symfony/Config/ChainableFileLocator.php
[`ChainableKernelFileLocator`]: ../src/Extension/Symfony/HttpKernel/ChainableKernelFileLocator.php
