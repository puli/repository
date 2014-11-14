Puli for Composer Agnostics
===========================


You are developing an application. You don't want to use the `Composer plugin`_.
Instead, you would like to manage your Puli_ repository manually.

If you don't know what Puli is or why you should use it, read
:doc:`../at-a-glance` first.

The Problem with Resources
--------------------------

*Resources*, in Puli's terminology, are machine-processed files that are *not*
PHP, such as CSS, JavaScript, XLIFF, YAML, XML or HTML files. Especially when
your project has many dependencies, these resources can be widely spread. To
load a resource, you did something like this in the past:

.. code-block:: php

    $contents = file_get_contents(__DIR__.'/../../vendor/acme/blog/resources/css/style.css');

This is more an exercise in counting directories than coding. Puli make this
easy for you.

Installation
------------

First, add Puli to your composer.json file:

.. code-block:: json

    {
        "require": {
            "puli/puli": "~1.0@dev"
        }
    }

Run ``composer install`` to install the plugin.

Creating a Repository
---------------------

Puli manages resources with the :class:`Puli\\Repository\\ResourceRepository`
class. A repository is much like a file system. You can "mount" files or
directories to paths in the repository:

.. code-block:: php

    use Puli\Repository\ResourceRepository;

    $repo = new ResourceRepository();
    $repo->add('/css', '/path/to/project/css');

Here, the local path ``/path/to/project/css`` is mapped to the Puli path
``/css``. The file ``style.css`` can now be loaded with the path
``/css/style.css``:

.. code-block:: php

    echo $repo->get('/css/style.css')->getContents();

Further Reading
---------------

We recommend to read the following documents now:

* :doc:`../repository-management/manual` explains the configuration of the
  repository in detail.
* :doc:`../working-with-resources` explains how to use the resources returned
  by the generated repository.

.. _Puli: https://github.com/puli/puli
.. _Composer plugin: https://github.com/puli/composer-puli-plugin
