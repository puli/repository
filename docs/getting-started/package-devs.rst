Puli for Package Developers
===========================

You are developing a reusable library. You use `Composer`_ to ship the library.
This guide will explain how you can export the resources of your package to
Puli.

If you don't know what Puli is or why you should support it, read
:doc:`../at-a-glance` first.

Using Puli Resources
--------------------

*Resources*, in Puli's terminology, are machine-processed files that are *not*
PHP, such as CSS, JavaScript, XLIFF, YAML, XML or HTML files. Your Composer
package needs to make these files accessible to its users. How would you do
that?

Installation
------------

First, add Puli's `Composer plugin`_ as suggestion to your composer.json file:

.. code-block:: json

    {
        "suggest": {
            "puli/composer-puli-plugin": "This package contains Puli resources. Require the plugin to use them."
        }
    }

This tells your users that your plugin is Puli-aware. Your users will love you
for that and will send you big Thank You presents for Christmas.

Next, export your resources to Puli paths:

.. code-block:: json

    {
        "name": "acme/blog",
        "suggest": {
            "puli/composer-puli-plugin": "This package contains Puli resources. Require the plugin to use them."
        },
        "extra": {
            "resources": {
                "export": {
                    "/acme/blog": "resources",
                    "/acme/blog/css": "assets/css"
                }
            }
        }
    }

The left hand side of each "export" entry is a Puli path. All Puli paths must
have the Composer vendor and package names as top-level directories.

The right hand side contains paths relative to the root of your package. You
can also map Puli paths to multiple directories by passing an array on the right
hand side:

.. code-block:: json

    {
        "name": "acme/blog",
        "suggest": {
            "puli/composer-puli-plugin": "This package contains Puli resources. Require the plugin to use them."
        },
        "extra": {
            "resources": {
                "export": {
                    "/acme/blog": ["assets", "resources"]
                }
            }
        }
    }

Using Resources
---------------

If you want to use the resources of your package or other packages in your code,
add an injection point for a
:class:`Puli\\Repository\\ResourceRepositoryInterface` instance:

.. code-block:: php

    namespace Acme/Blog/Config/Loader;

    use Puli\Repository\ResourceRepositoryInterface;

    class ConfigurationLoader
    {
        public function loadConfiguration(ResourceRepositoryInterface $repo)
        {
            // ...
        }
    }

The end users of that class will pass a repository that the Composer plugin
generates for them. Read :doc:`application-devs` to learn more about that.

Use the methods in :class:`Puli\\Repository\\ResourceRepositoryInterface` to
retrieve resources from the repository:

.. code-block:: php

    // ...
    class ConfigurationLoader
    {
        public function loadConfiguration(ResourceRepositoryInterface $repo)
        {
            $yaml = $repo->get('/acme/blog/config/config.yml')->getContents();

            // ...
        }
    }

.. note::

    Why not simply use relative file paths? The benefit of using Puli here is
    that the users of your package can override the ``config.yml`` file used
    in the example. If you use a relative file path, that's not possible.

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
