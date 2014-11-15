Puli for Application Developers
===============================

You are developing an application. You use `Composer`_ to install required
packages. You either bootstrap the application yourself or use a framework that
does this for you. This guide explains how to use Puli_ to access resources in
your installed Composer packages.

If you don't know what Puli is or why you should use it, read
:doc:`../at-a-glance` first.

The Problem with Resources
--------------------------

*Resources*, in Puli's terminology, are machine-processed files that are *not*
PHP, such as CSS, JavaScript, XLIFF, YAML, XML or HTML files. Many Composer
packages ship such files and you want to use them in your application. In the
past, you did something like this:

.. code-block:: php

    echo __DIR__.'/../../vendor/acme/blog/resources/css/style.css';

This is more an exercise in counting directories than coding. Puli allows you
to do better.

Installation
------------

First, add Puli's `Composer plugin`_ to your composer.json file:

.. code-block:: json

    {
        "require": {
            "puli/composer-puli-plugin": "~1.0@dev"
        }
    }

Run ``composer install`` to install the plugin.

Now that the plugin is installed, you want to run ``composer install`` *again*.
The plugin will now generate a *resource repository* that you need to include
in your application:

.. code-block:: php

    $repo = require __DIR__.'/vendor/resource-repository.php';

The variable ``$repo`` contains an instance of
:class:`Puli\\Repository\\ResourceRepositoryInterface`. This repository allows
to locate and output the file ``style.css`` much easier than before:

.. code-block:: php

    echo $repo->get('/acme/blog/css/style.css')->getContents();

Using Puli-Aware Packages
-------------------------

*Puli-aware* Composer packages map their resources to Puli paths in their
composer.json files. For example, the composer.json file of the package
"acme/blog" could look like this:

.. code-block:: json

    {
        "name": "acme/blog",
        "extra": {
            "resources": {
                "/acme/blog": "resources"
            }
        }
    }

This means that the directory `resources/` in the package - and all files
therein - can be accessed using the Puli path `/acme/blog`:

.. code-block:: php

    echo $repo->get('/acme/blog/css/style.css')->getContents();

Using Puli-Unaware Packages
---------------------------

If you use a Composer package that does not map its resources to Puli paths, you
should submit a pull request that adds the relevant lines to its composer.json
file. Until the pull request is merged, you can manually map the package's
resources in your application's composer.json:

.. code-block:: json

    {
        "require": {
            "acme/blog": "*"
        },
        "extra": {
            "resources": {
                "/acme/blog": "vendor/acme/blog/resources"
            }
        }
    }

Run ``composer install`` to update the generated repository. Again, you can now
access all files in the `resources/` directory of the package using the Puli
path `/acme/blog`. Once your pull request is merged, you can remove the
entry and update the package.

Mapping Application Resources
-----------------------------

Of course, your application itself also contains resources that you want to
access. By convention, the resources of the application are mapped to the Puli
path `/app`. This is done by adding the following lines to the composer.json
file of your application:

.. code-block:: json

    {
        "extra": {
            "resources": {
                "/app": "resources"
            }
        }
    }

Run ``composer install`` to refresh the generated repository. Your application
resources can be accessed using the Puli path ``/app`` now:

.. code-block:: php

    echo $repo->get('/app/css/style.css')->getContents();

Further Reading
---------------

The following documents might be interesting for you:

* :doc:`../working-with-resources` explains how to use the resources returned
  by the generated repository.
* :doc:`../repository-management/composer` explains more details about the
  repository configuration.

.. _Puli: https://github.com/puli/puli
.. _Composer: https://getcomposer.org
.. _Composer plugin: https://github.com/puli/composer-puli-plugin
