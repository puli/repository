Puli for Application Developers
===============================

You are developing an application. You use `Composer`_ to install required
packages. You either bootstrap the application yourself or use a framework that
does this for you. This guide will explain how you can use Puli to access
resources in your installed Composer packages.

If you don't know what Puli is or why you should use it, read
:doc:`../at-a-glance` first.

Using Puli Resources
--------------------

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
to locate and output the file ``style.css`` much easier:

.. code-block:: php

    echo $repo->get('/acme/blog/css/style.css')->getContents();

Using Puli-Aware Packages
-------------------------

*Puli-aware* Composer packages export their resources to Puli paths in their
composer.json files. For example, the composer.json file of the package
"acme/blog" could look like this:

.. code-block:: json

    {
        "name": "acme/blog",
        "extra": {
            "resources": {
                "export": {
                    "/acme/blog": "resources"
                }
            }
        }
    }

This means that the directory `resources/` in the package - and all files
therein - can be accessed using the Puli path `/acme/blog`:

.. code-block:: php

    echo $repo->get('/acme/blog/css/style.css')->getContents();

Using Puli-Unaware Packages
---------------------------

If you use a Composer package that does not export its resources for Puli, you
should submit a pull request that adds the relevant lines to its composer.json
file. Until the pull request is merged, you can manually export the package's
resources in your application's composer.json:

.. code-block:: json

    {
        "require": {
            "acme/blog": "*"
        },
        "extra": {
            "resources": {
                "override": {
                    "/acme/blog": "vendor/acme/blog/resources"
                }
            }
        }
    }

Run ``composer install`` to update the generated repository. Again, you can now
access all files in the `resources/` directory of the package using the Puli
path `/acme/blog`. Once your pull request is merged, you can remove the
"override" entry and update the package.

Using Application Resources
---------------------------

Of course, your application itself also contains resources that you want to
access. By convention, the resources of the application are exported to the
Puli path `/app`. This is done by adding the following lines to the
composer.json file of your application:

.. code-block:: json

    {
        "extra": {
            "resources": {
                "export": {
                    "/app": "resources"
                }
            }
        }
    }

Puli paths can also be mapped to multiple directories:

.. code-block:: json

    {
        "extra": {
            "resources": {
                "export": {
                    "/app": ["assets", "resources"]
                }
            }
        }
    }

Run ``composer install`` to refresh the generated repository. Your application
resources can be accessed using the Puli path ``/app`` now:

.. code-block:: php

    echo $repo->get('/app/css/style.css')->getContents();

Handling Resources
------------------

The resources returned by the Puli repository implement
:class:`Puli\\Resource\\ResourceInterface`. This interface only contains two
methods:

* :method:`Puli\\Resource\\ResourceInterface::getPath`: Returns the Puli path
  of the resource.

* :method:`Puli\\Resource\\ResourceInterface::getName`: Returns only the "name"
  part of the path. If the path is ``/app/css/style.css``, the name is
  ``style.css``.

Resources that are stored on the file system implement
:class:`Puli\\Filesystem\\Resource\\LocalResourceInterface`. This interface
contains methods for accessing the file system paths of the resource:

* :method:`Puli\\Filesystem\\Resource\\LocalResourceInterface::getLocalPath`:
  Returns the path of the resource on the file system.

* :method:`Puli\\Filesystem\\Resource\\LocalResourceInterface::getAllLocalPaths`:
  Returns all file system paths of the resource. If a resource was overridden,
  this method also returns the overridden paths. The last entry of the returned
  array is always equal to
  :method:`Puli\\Filesystem\\Resource\\LocalResourceInterface::getLocalPath`.

File resources implement the interface
:class:`Puli\\Resource\\FileResourceInterface`. This interface provides access
to the contents of the file:

* :method:`Puli\\Resource\\FileResourceInterface::getContents`: Returns the file
  contents.

* :method:`Puli\\Resource\\FileResourceInterface::getSize`: Returns the size of
  the file.

* :method:`Puli\\Resource\\FileResourceInterface::getLastModified`: Returns when
  the file was last modified. Useful if you want to store the contents in a
  cache.

Directory resources, at last, implement
:class:`Puli\\Resource\\DirectoryResourceInterface`. This interface provides
access to the contents of the directory:

* :method:`Puli\\Resource\\DirectoryResourceInterface::listEntries`: Lists the
  resources in the directory.

* :method:`Puli\\Resource\\DirectoryResourceInterface::get`: Returns a single
  entry of the directory by its name.

* :method:`Puli\\Resource\\DirectoryResourceInterface::contains`: Returns
  whether the directory contains an entry with a specific name.

That's all you need to know for a start.

.. _Composer: https://getcomposer.org
.. _Composer plugin: https://github.com/puli/composer-puli-plugin
