Package Guidelines
==================

This guide explains how to configure reusable packages to make their resources
usable with Puli_.

If you don't know what Puli is or why you should use it, read
:doc:`at-a-glance` first.

Composer Configuration
----------------------

Add the Puli library to your composer.json file:

.. code-block:: json

    {
        "require": {
            "puli/puli": "~1.0@dev"
        }
    }

This is necessary to access the Puli API in your PHP code. If you don't need the
Puli API, you can skip this dependency.

Next, add Puli's `Composer plugin`_ as suggestion:

.. code-block:: json

    {
        "suggest": {
            "puli/puli-composer-plugin": "This package contains Puli resources. Require the plugin to use them."
        }
    }

This tells your users that your package is Puli-aware. Your users will love you
for that and will send you big Thank You presents for Christmas.

Run ``composer update`` to finish the installation.

Mapping Resources
-----------------

You can map the resources of your package in a puli.json file in the root
directory of your package:

.. code-block:: json

    {
        "resources": {
            "/acme/blog": "res",
            "/acme/blog/css": "assets/css"
        }
    }

Read :doc:`repository-configuration` to learn more about mapping resources in
puli.json.

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

The end user of your package needs to pass Puli's generated repository to your
class. Read :doc:`getting-started` to learn more about that.

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

.. _Puli: https://github.com/puli/puli
.. _Composer plugin: https://github.com/puli/puli-composer-plugin
