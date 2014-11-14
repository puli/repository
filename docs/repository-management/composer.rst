Managing a Repository with Composer
===================================

This guide explains how to manage your Puli_ repository with the `Puli plugin
for Composer`_. The plugin should be installed already. If it is not, follow
the instructions in :doc:`../getting-started/application-devs`.

If you don't know what Puli is or why you should use it, read
:doc:`../at-a-glance` first.

Exporting Resources
-------------------

Resources can be exported to Puli paths by adding them to the "export" key
in composer.json:

.. code-block:: json

    {
        "name": "acme/blog",
        "extra": {
            "resources": {
                "export": {
                    "/acme/blog": "resources",
                }
            }
        }
    }

The keys of the entries in "export" are Puli paths. All of these paths *must*
have the Composer vendor and package name as top-level directories. However,
you can also map more specific paths:

.. code-block:: json

    {
        "name": "acme/blog",
        "extra": {
            "resources": {
                "export": {
                    "/acme/blog/css": "assets/css",
                }
            }
        }
    }

The right hand side of the "export" key contains paths relative to the root
of your Composer package. Usually, that's the directory that contains your
composer.json file.

You can map the same Puli path to multiple directories:

.. code-block:: json

    {
        "name": "acme/blog",
        "extra": {
            "resources": {
                "export": {
                    "/acme/blog": ["assets", "resources"],
                }
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
                "export": {
                    "/acme/blog/css": "assets/css",
                    "/acme/blog/css/reset.css": "generic/reset.css",
                }
            }
        }
    }

Tagging Resources
-----------------

You can tag mapped resources in order to indicate that they support specific
features. For example, assume that all XLIFF translation files in the
"acme/blog" package should be registered with the ``\Acme\Translator`` class.
You can tag resources by adding them to the "tag" key in composer.json:

.. code-block:: json

    {
        "name": "acme/blog",
        "extra": {
            "resources": {
                "export": {
                    "/acme/blog": "resources",
                },
                "tag": {
                    "/acme/blog/translations/*.xlf": "acme/translator/xlf"
                }
            }
        }
    }

The left side of the array is a path or a glob that selects one or more
resources in the repository. The right side contains one or more tag that should
be added to the selected resources.

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
path you want to override to the "override" key:

.. code-block:: json

    {
        "name": "acme/blog-extension",
        "require": {
            "acme/blog": "*"
        },
        "extra": {
            "resources": {
                "override": {
                    "/acme/blog/css": "assets/css"
                }
            }
        }
    }

The resources in the "acme/blog-extension" package are now preferred over those
in the "acme/blog" package. If a resource was not found in the overriding
package, the resource from the original package will be returned instead.

You can get all paths for an overridden resource using the
:method:`Puli\\Filesystem\\Resource\\LocalResourceInterface::getAllLocalPaths`
method. The paths are returned in the order in which they were overridden,
starting with the originally exported path:

.. code-block:: php

    print_r($repo->get('/acme/blog/css/style.css')->getAllLocalPaths());
    // Array
    // (
    //     [0] => /path/to/vendor/acme/blog/assets/css/style.css
    //     [1] => /path/to/vendor/acme/blog-extension/assets/css/style.css
    // )

Handling Override Conflicts
---------------------------

If multiple packages try to override the same path, an
:class:`Puli\\Extension\\Composer\\RepositoryLoader\\OverrideConflictException`
will be thrown and the overrides will be ignored. The reason for this behavior
is that Puli can't know in which order the overrides should be applied.

You can fix this problem by adding the key "override-order" to the composer.json
file of the **root project**. In this key, you can define the order in
which packages should override a path in the repository:

.. code-block:: json

    {
        "name": "my/application",
        "require": {
            "acme/blog": "*",
            "acme/blog-extension": "*"
        },
        "extra": {
            "resources": {
                "override": {
                    "/acme/blog/css": "resources/acme/blog/css",
                },
                "override-order": {
                    "/acme/blog/css": ["acme/blog-extension", "my/application"]
                }
            }
        }
    }

In this example, the application requires the package "acme/blog" and another
package "acme/blog-extension" which overrides the ``/acme/blog/css`` directory.
To complicate things, the application overrides this path as well. Through
the "override-order" key, you can tell Puli that the overrides in
"vendor/application" should be preferred over those in "acme/blog-extension".

If you query the path of the file style.css again, and if that file exists in
all three packages, you will get a result like this:

.. code-block:: php

    echo $repo->get('/acme/blog/css/style.css')->getLocalPath();
    // => /path/to/resources/acme/blog/css/style.css

    print_r($repo->get('/acme/blog/css/style.css')->getAllLocalPaths());
    // Array
    // (
    //     [0] => /path/to/vendor/acme/blog/assets/css/style.css
    //     [1] => /path/to/vendor/acme/blog-extension/assets/css/style.css
    //     [2] => /path/to/resources/acme/blog/css/style.css
    // )

Further Reading
---------------


.. _Puli: https://github.com/puli/puli
.. _Puli plugin for Composer: https://github.com/puli/composer-puli-plugin
