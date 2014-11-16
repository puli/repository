Puli for Package Developers
===========================

You are developing a reusable library. You use `Composer`_ to ship the library.
This guide explains how to export the resources of your package to Puli_.

If you don't know what Puli is or why you should use it, read
:doc:`../at-a-glance` first.

The Problem with Resources
--------------------------

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

Mapping Resources
-----------------

Next, map your resources to Puli paths:

.. code-block:: json

    {
        "name": "acme/blog",
        "suggest": {
            "puli/composer-puli-plugin": "This package contains Puli resources. Require the plugin to use them."
        },
        "extra": {
            "puli": {
                "resources": {
                    "/acme/blog": "resources",
                    "/acme/blog/css": "assets/css"
                }
            }
        }
    }

The left hand side of each "resources" entry is a Puli path. By convention, all
Puli paths should have the Composer vendor and package names as top-level
directories. The right hand side contains paths relative to the root of your
package.

Using the Repository
--------------------

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

Further Reading
---------------

We recommend to read the following documents now:

* :doc:`../working-with-resources` explains how to use the resources returned
  by the generated repository.
* :doc:`../repository-management/composer` explains more details about the
  repository configuration.

.. _Puli: https://github.com/puli/puli
.. _Composer: https://getcomposer.org
.. _Composer plugin: https://github.com/puli/composer-puli-plugin
