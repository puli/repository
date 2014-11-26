Mapping Resources
=================

This guide explains how to map resources with Puli_. Puli should be installed
already. If it is not, follow the instructions in :doc:`getting-started`.

If you don't know what Puli is or why you should use it, read
:doc:`at-a-glance` first.

Mapping Resources
-----------------

Resources can be mapped to Puli paths by adding them to the "resources" key in
puli.json:

.. code-block:: json

    {
        "resources": {
            "/acme/blog": "res"
        }
    }

The keys of the entries in "resources" are Puli paths. By convention, your
package should use its vendor and package names as top-level directories.

.. tip::

    If you develop an application that is not going to be shared, use ``/app``
    as top-level directory for your Puli paths.

Run ``puli dump`` to generate the resource repository for your mapping:

.. code-block:: bash

    $ puli dump

Now you can access the resources in your PHP code:

.. code-block:: php

    $repo = require __DIR__.'/.puli/resource-repository.php';

    // res/views/index.html
    echo $repo->get('/acme/blog/views/index.html')->getContents();

You can also map to more specific paths:

.. code-block:: json

    {
        "resources": {
            "/acme/blog/css": "assets/css"
        }
    }

The right hand side of the "resources" key contains paths relative to the
directory that contains the puli.json file.

You can map the same Puli path to multiple directories:

.. code-block:: json

    {
        "resources": {
            "/acme/blog": ["assets", "ress"]
        }
    }

Now, assets from both the ``assets/`` and the ``res/`` directory are accessible
by the same Puli path ``/acme/blog``:

.. code-block:: php

    // assets/css/style.css
    echo $repo->get('/acme/blog/css/style.css')->getContents();

    // res/config/config.xml
    echo $repo->get('/acme/blog/config/config.xml')->getContents();

If the directories contain entries with the same name, entries of latter
directories (here: ``res/``) *override* entries of the former ones. For example,
if both directories contain a file ``.htaccess``, the one in the ``res/``
directory will be used by default:

.. code-block:: php

    // res/.htaccess
    echo $repo->get('/acme/blog/.htaccess')->getContents();

Read `Overriding Resources`_ to learn more about this topic.

You can also map Puli paths to individual files. This is helpful if you need
to cherry-pick files from specific locations:

.. code-block:: json

    {
        "resources": {
            "/acme/blog/css": "assets/css",
            "/acme/blog/css/reset.css": "generic/reset.css"
        }
    }

Referencing Other Packages
--------------------------

Sometimes it is necessary to map paths that are located in other packages. This
happens especially when you use packages that don't map their resources by
themselves.

You can use the prefix ``@package-name:`` to reference the install path of
other packages:

.. code-block:: json

    {
        "resources": {
            "/acme/theme/css": "@acme/theme:assets/css"
        }
    }

The example above will map the Puli path ``/acme/theme/css`` to the
``assets/css`` directory in the "acme/theme" package.

If the "acme/theme" package is *optional*, the above will not work. You will
get an exception when dumping the repository without having the "acme/theme"
package installed. For optional packages, use the ``@?package-name:`` syntax
instead:

.. code-block:: json

    {
        "resources": {
            "/acme/theme/css": "@?acme/theme:assets/css"
        }
    }

This resource definition will silently be ignored if the "acme/theme" package
is not installed.

Overriding Resources
--------------------

Each package can override the resources of another package. To do so, add the
name of the package you want to override to the "override" key:

.. code-block:: json

    {
        "resources": {
            "/acme/blog/css": "assets/css"
        },
        "override": "acme/blog"
    }

The resources in the "acme/blog-extension" package are now preferred over those
in the "acme/blog" package. If a resource was not found in the overriding
package, the resource from the original package will be returned instead.

You can get all paths for an overridden resource using the
:method:`Puli\\Filesystem\\Resource\\LocalResourceInterface::getAllLocalPaths`
method. The paths are returned in the order in which they were overridden,
starting with the original path:

.. code-block:: php

    print_r($repo->get('/acme/blog/css/style.css')->getAllLocalPaths());
    // Array
    // (
    //     [0] => /path/to/vendor/acme/blog/assets/css/style.css
    //     [1] => /path/to/vendor/acme/blog-extension/assets/css/style.css
    // )

Handling Override Conflicts
---------------------------

If multiple packages try to override the same path, a
:class:`Puli\\RepositoryManager\\Repository\\ResourceConflictException`
will be thrown. The reason for this behavior is that Puli can't know in which
order the overrides should be applied.

There are two possible fixes for this problem:

1. One of the packages explicitly adds the name of the other package to its
   "override" key.

2. You specify the key "package-order" in the puli.json file of the
   **project root**.

With the "package-order" key you can specify in which order the packages
should be loaded:

.. code-block:: json

    {
        "resources": {
            "/acme/blog/css": "res/acme/blog/css"
        },
        "package-order": ["acme/blog-extension-1", "acme/blog-extension-2"]
    }

In this example, the application requires the package "acme/blog" and two
packages "acme/blog-extension-1" and  "acme/blog-extension-2" which both
override the ``/acme/blog/css`` directory. Neither package defines the other one
in its "override" key.

Through the "package-order" key, you tell Puli that the resources from
"acme/blog-extension-1" are loaded before those in "acme/blog-extension-2".
This means that "acme/blog-extension-2" will override "acme/blog-extension-1".

If you query the path of the file style.css again, and if that file exists in
all three packages, you will get a result like this:

.. code-block:: php

    echo $repo->get('/acme/blog/css/style.css')->getLocalPath();
    // => /path/to/res/acme/blog/css/style.css

    print_r($repo->get('/acme/blog/css/style.css')->getAllLocalPaths());
    // Array
    // (
    //     [0] => /path/to/vendor/acme/blog/assets/css/style.css
    //     [1] => /path/to/vendor/acme/blog-extension-1/assets/css/style.css
    //     [2] => /path/to/vendor/acme/blog-extension-2/assets/css/style.css
    // )

Further Reading
---------------

* :doc:`repositories` explains how to manage repositories by hand.
* :doc:`tags` explains how to tag resources that share common functionality.
* :doc:`uris` teaches you how to use multiple resource repositories side by side.

.. _Puli: https://github.com/puli/puli
.. _Puli plugin for Composer: https://github.com/puli/composer-puli-plugin
