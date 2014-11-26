Tags
====

This guide explains how you can use tags to group and find Puli_ resources by
their functionality.

If you don't know what Puli is or why you should use it, read :doc:`at-a-glance`
first.

Tagging with Composer
---------------------

You can tag mapped resources in order to indicate that they support specific
features. For example, assume that all XLIFF translation files in the
"acme/blog" package should be registered with the ``\Acme\Translator`` class.
You can tag resources by adding them to the "tags" key in puli.json:

.. code-block:: json

    {
        "resources": {
            "/acme/blog": "res"
        },
        "tags": {
            "/acme/blog/trans/*.xlf": "acme/translator/xlf"
        }
    }

The left side of the array is a path or a glob that selects one or more
resources in the repository. The right side contains one or more tags that
should be added to the selected resources.

Finding Tagged Resources
------------------------

The tagged resources can be retrieved with the
:method:`Puli\\Repository\\ResourceRepositoryInterface::findByTag` method of the
resource repository:

.. code-block:: php

    foreach ($repo->findByTag('acme/translator/xlf') as $resource) {
        // ...
    }

Use :method:`Puli\\Repository\\ResourceRepositoryInterface::getTags` to read all
tags that have been registered with the repository:

.. code-block:: php

    $tags = $repo->getTags();

This method will return an array of strings, i.e. the names of all registered
tags.

Manual Tagging
--------------

Use the :method:`Puli\\Repository\\ManageableRepositoryInterface::tag` method
on the repository to tag resources manually:

.. code-block:: php

    $repo->tag('/translations/*.xlf', 'acme/translator/xlf');

You can remove one or all tags from a resource using the
:method:`Puli\\Repository\\ManageableRepositoryInterface::untag` method:

.. code-block:: php

    // Remove the tag "acme/translator/xlf"
    $repo->untag('/translations/*.xlf', 'acme/translator/xlf');

    // Remove all tags
    $repo->untag('/translations/*.xlf');

Further Reading
---------------

Read :doc:`uris` to learn how to use multiple repositories side by side.

.. _Puli: https://github.com/puli/puli
