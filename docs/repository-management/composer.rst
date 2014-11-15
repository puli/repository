Managing a Repository with Composer
===================================

This guide explains how to manage your Puli_ repository with the `Puli plugin
for Composer`_. The plugin should be installed already. If it is not, follow
the instructions in :doc:`../getting-started/application-devs`.

If you don't know what Puli is or why you should use it, read
:doc:`../at-a-glance` first.

Exporting Resources
-------------------

Resources can be mapped to Puli paths by adding them to the "resources" key in
composer.json:

.. code-block:: json

    {
        "name": "acme/blog",
        "extra": {
            "resources": {
                "/acme/blog": "resources"
            }
        }
    }

The keys of the entries in "resources" are Puli paths. By convention, your
package should use its vendor and package names as top-level directories.

You can also map to more specific paths:

.. code-block:: json

    {
        "name": "acme/blog",
        "extra": {
            "resources": {
                "/acme/blog/css": "assets/css"
            }
        }
    }

The right hand side of the "resources" key contains paths relative to the root
of your Composer package. Usually, that's the directory that contains your
composer.json file.

You can map the same Puli path to multiple directories:

.. code-block:: json

    {
        "name": "acme/blog",
        "extra": {
            "resources": {
                "/acme/blog": ["assets", "resources"]
            }
        }
    }

Now, assets from both the ``assets/`` and the ``resources/`` directory are
accessible by the same Puli path ``/acme/blog``:

.. code-block:: php

    // assets/css/style.css
    $repo->get('/acme/blog/css/style.css')->getContents();

    // resources/config/config.xml
    $repo->get('/acme/blog/config/config.xml')->getContents();

If the directories contain entries with the same name, entries of latter
directories (here: ``resources/``) *override* entries of the former ones. For
example, if both directories contain a file ``.htaccess``, the one in the
``resources/`` directory will be used by default:

.. code-block:: php

    // resources/.htaccess
    $repo->get('/acme/blog/.htaccess')->getContents();

Read `Overriding Resources`_ to learn more about this topic.

You can also map Puli paths to individual files. This is helpful if you need
to cherry-pick files from specific locations:

.. code-block:: json

    {
        "name": "acme/blog",
        "extra": {
            "resources": {
                "/acme/blog/css": "assets/css",
                "/acme/blog/css/reset.css": "generic/reset.css"
            }
        }
    }

Tagging Resources
-----------------

You can tag mapped resources in order to indicate that they support specific
features. For example, assume that all XLIFF translation files in the
"acme/blog" package should be registered with the ``\Acme\Translator`` class.
You can tag resources by adding them to the "resource-tags" key in composer.json:

.. code-block:: json

    {
        "name": "acme/blog",
        "extra": {
            "resources": {
                "/acme/blog": "resources"
            },
            "resource-tags": {
                "/acme/blog/translations/*.xlf": "acme/translator/xlf"
            }
        }
    }

The left side of the array is a path or a glob that selects one or more
resources in the repository. The right side contains one or more tags that
should be added to the selected resources.

The tagged resources can then be retrieved with the
:method:`Puli\\Repository\\ResourceRepositoryInterface::getByTag` method of the
resource repository:

.. code-block:: php

    foreach ($repo->getByTag('acme/translator/xlf') as $resource) {
        // ...
    }

Overriding Resources
--------------------

Each package can override the resources of another package. To do so, add the
name of the package you want to override to the "override" key:

.. code-block:: json

    {
        "name": "acme/blog-extension",
        "require": {
            "acme/blog": "*"
        },
        "extra": {
            "resources": {
                "/acme/blog/css": "assets/css"
            },
            "override": "acme/blog"
        }
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
:class:`Puli\\Extension\\Composer\\RepositoryBuilder\\ResourceConflictException`
will be thrown. The reason for this behavior is that Puli can't know in which
order the overrides should be applied.

There are two possible fixes for this problem:

1. One of the packages explicitly adds the name of the other package to its
   "override" key.

2. You specify the key "package-order" in the composer.json file of the
   **root project**.

With the "package-order" key you can specify in which order the packages
should be loaded:

.. code-block:: json

    {
        "require": {
            "acme/blog": "*",
            "acme/blog-extension-1": "*",
            "acme/blog-extension-2": "*"
        },
        "extra": {
            "resources": {
                "/acme/blog/css": "resources/acme/blog/css"
            },
            "package-order": ["acme/blog-extension-1", "acme/blog-extension-2"]
        }
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
    // => /path/to/resources/acme/blog/css/style.css

    print_r($repo->get('/acme/blog/css/style.css')->getAllLocalPaths());
    // Array
    // (
    //     [0] => /path/to/vendor/acme/blog/assets/css/style.css
    //     [1] => /path/to/vendor/acme/blog-extension-1/assets/css/style.css
    //     [1] => /path/to/vendor/acme/blog-extension-2/assets/css/style.css
    // )

Further Reading
---------------

Read :doc:`../uris` to learn how to use multiple repositories side by side.

.. _Puli: https://github.com/puli/puli
.. _Puli plugin for Composer: https://github.com/puli/composer-puli-plugin
