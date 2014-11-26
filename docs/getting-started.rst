.. index::
    single: Getting Started
    single: Installation

.. |trade| unicode:: U+2122

Getting Started
===============

This section explains how to get started quickly with Puli_. If you don't know
what Puli is or why you should use it, read :doc:`at-a-glance` first.

Requirements
------------

Puli requires PHP 5.3.9 or higher.

Stability
---------

Puli is not yet available in a stable version. The latest release is
|release|.

Installation
------------

First, add Puli to your composer.json file:

.. code-block:: json

    {
        "require": {
            "puli/puli": "~1.0@dev"
        },
        "minimum-stability": "dev"
    }

During development, you will also need the `Puli CLI`_ and the
`Composer plugin`_:

.. code-block:: json

    {
        "require-dev": {
            "puli/puli-cli": "~1.0@dev",
            "puli/puli-composer-plugin": "~1.0@dev"
        }
    }

Run ``composer update`` to install the packages. Type ``vendor/bin/puli`` to
check whether the Puli CLI was installed successfully:

.. code-block:: bash

    $ vendor/bin/puli

.. note::

    If "vendor-dir" or "bin-dir" is set in composer.json, the path to the
    ``puli`` command will be different.

To save you from typing ``vendor/bin/puli`` all the time, add the ``vendor/bin``
directory to your path:

* On Unix-based systems, add the following line to ~/.bashrc:

    .. code-block:: text

        export PATH="$PATH:vendor/bin"

  Apply the changes with the ``source`` command:

    .. code-block:: bash

        $ source ~/.bashrc

* On Windows, append ``;vendor/bin`` to the environment variable "Path". In
  Windows 7, you can find the environment variables in the Control Panel
  under "System" → "Advanced system settings" → "Advanced" →
  "Environment Variables".

.. caution::

    If "vendor-dir" or "bin-dir" is set in composer.json, adapt the content
    of the path accordingly.

Now you should be able to run ``puli`` without the ``vendor/bin/`` prefix.

Mapping Application Resources
-----------------------------

By convention, the resources of applications are mapped to the Puli path
``/app``. To do this, create a file named puli.json in the root directory of
your project and insert the following contents:

.. code-block:: json

    {
        "resources": {
            "/app": "res"
        }
    }

The left-hand side of the "resources" block contains Puli paths. The right-hand
side points to paths in your project. In this example, the  Puli path ``/app``
is mapped to the directory ``res`` in the project.

Run ``puli dump`` to generate the resource repository:

.. code-block:: bash

    $ puli dump

The generated repository can now be loaded and used in PHP:

.. code-block:: php

    $repo = require __DIR__.'/.puli/resource-repository.php';

    echo $repo->get('/app/css/style.css')->getContents();

Using Puli-Aware Packages
-------------------------

*Puli-aware* Composer packages ship puli.json files just like the one in your
application. For example, the puli.json file of the package "acme/blog" could
look like this:

.. code-block:: json

    {
        "resources": {
            "/acme/blog": "res"
        }
    }

The package maps the Puli path ``/acme/blog`` to its ``res`` directory. That
directory - and all files therein - can be accessed by the Puli path
``/acme/blog``:

.. code-block:: php

    echo $repo->get('/acme/blog/css/style.css')->getContents();

.. note::

    By convention, the Puli paths of Composer packages always start with the
    package's vendor and package name as top-level directories.

Using Puli-Unaware Packages
---------------------------

If you use a Composer package that does not ship a puli.json, you should submit
a pull request that adds that file. Until the pull request is merged, you can
manually map the package's resources in your application's puli.json:

.. code-block:: json

    {
        "resources": {
            "/acme/blog": "@acme/blog:res"
        }
    }

.. note::

    The prefix ``@acme/blog:`` points to the install path of the "acme/blog"
    package.

Run ``puli dump`` to regenerate the resource repository. You can then access all
files in the ``res`` directory of the package using the Puli path
``/acme/blog``.

If the "acme/blog" package is not installed when you dump the repository, you
will get an exception. This is a problem if "acme/blog" is an optional
dependency. To fix this, prefix the reference with ``@?``:

.. code-block:: json

    {
        "resources": {
            "/acme/blog": "@?acme/blog:res"
        }
    }

If the "acme/blog" package is not installed, the above resource definition will
now silently be ignored.

Further Reading
---------------

* :doc:`../working-with-resources` explains how to use the resources returned
  by the generated repository.
* :doc:`../repository-configuration` explains more details about the
  repository configuration.

.. _Puli: https://github.com/puli/puli
.. _Puli Package Manager: https://github.com/puli/puli-package-manager
.. _Puli CLI: https://github.com/puli/puli-cli
.. _Composer Plugin: https://github.com/puli/puli-composer-plugin
.. _Composer: https://getcomposer.org
