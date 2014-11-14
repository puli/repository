Locating Config Files with Puli
===============================

Puli provides a file locator for the `Symfony Config component`_ that locates
configuration files using a Puli repository. With this locator, you can
refer from one configuration file to another via its Puli path:

.. code-block:: yaml

    # routing.yml
    _acme_demo:
        resource: /acme/demo-bundle/config/routing.yml

Installation
------------

First you need to install the `Puli bridge`_ with Composer_. Add the
"puli/symfony-puli-bridge" package to composer.json:

.. code-block:: json

    {
        "require": {
            "puli/symfony-puli-bridge": "~1.0@dev"
        }
    }

Run ``composer install`` to install the extension.

Configuration
-------------

To locate configuration files with Puli, create a new
:class:`Puli\\Extension\\Symfony\\Config\\PuliFileLocator` and pass it to your
file loaders:

.. code-block:: php

    use Puli\Extension\Symfony\Config\PuliFileLocator;
    use Symfony\Component\Routing\Loader\YamlFileLoader;

    $loader = new YamlFileLoader(new PuliFileLocator($repo));

    // Locates the file from Puli's repository
    $routes = $loader->load('/acme/blog/config/routing.yml');

You need to pass Puli's resource repository to the constructor of the
:class:`Puli\\Extension\\Symfony\\Config\\PuliFileLocator`. If you don't know
how to create that, read the :doc:`../getting-started` guide.

Chained Locators
----------------

If you want to use the
:class:`Puli\\Extension\\Symfony\\Config\\PuliFileLocator` and Symfony's
conventional ``FileLocator`` side by side, you can use them both by wrapping
them into a :class:`Puli\\Extension\\Symfony\\Config\\FileLocatorChain`:

.. code-block:: php

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

:class:`Puli\\Extension\\Symfony\\Config\\ChainableFileLocator` is a simple
extension of Symfony's ``FileLocator`` that supports the interface required by
the locator chain. Note that this locator must come **after** the
:class:`Puli\\Extension\\Symfony\\Config\\PuliFileLocator` in the chain.

Puli also provides a chainable version of the file locator bundled with the
`Symfony HttpKernel component`_: Use the
:class:`Puli\\Extension\\Symfony\\HttpKernel\\ChainableKernelFileLocator`
if you want to load configuration files from Symfony bundles:

.. code-block:: php

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

Take care again that the
:class:`Puli\\Extension\\Symfony\\HttpKernel\\ChainableKernelFileLocator`
comes last in the chain.

Limitations
-----------

Due to limitations with Symfony's ``FileLocatorInterface``, relative file
references are not properly supported. Let's load some routes for example:

.. code-block:: php

    $routes = $loader->load('/acme/blog/config/routing-dev.yml');

Assume that this file contains the following import:

.. code-block:: yaml

    # routing-dev.yml
    _main:
        resource: routing.yml

What happens if we override this file in the Puli repository?

.. code-block:: php

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

This is a limitation in Symfony and cannot be worked around. For this
reason, :class:`Puli\\Extension\\Symfony\\Config\\PuliFileLocator` does not
support relative file paths.

.. _Puli: https://github.com/puli/puli
.. _Puli bridge: https://github.com/puli/symfony-puli-bridge
.. _Composer: https://getcomposer.org
.. _Symfony: http://symfony.com
.. _Symfony Config component: http://symfony.com/doc/current/components/config/introduction.html
.. _Symfony HttpKernel component: http://symfony.com/doc/current/components/http_kernel/introduction.html
