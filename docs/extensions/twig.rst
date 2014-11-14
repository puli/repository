Loading Twig Templates with Puli
================================

Puli provides an extension for the `Twig templating engine`_. With this
extension, you can refer to template files through Puli paths:

.. code-block:: php

    echo $twig->render('/acme/blog/views/show.html.twig');

Installation
------------

You can install the extension with Composer_. Add the "puli/twig-puli-extension"
package to composer.json:

.. code-block:: json

    {
        "require": {
            "puli/twig-puli-extension": "~1.0@dev"
        }
    }

In order to activate the extension, create a new
:class:`Puli\\Extension\\Twig\\PuliTemplateLoader` and register it with Twig.
The loader turns a Puli path into an absolute path when loading a template.
Then, create a new :class:`Puli\\Extension\\Twig\\PuliExtension` and add it to
Twig. The extension takes care that templates loaded by the
:class:`Puli\\Extension\\Twig\\PuliTemplateLoader` are processed correctly.

.. code-block:: php

    use Puli\Extension\Twig\PuliTemplateLoader;
    use Puli\Extension\Twig\PuliExtension;

    $twig = new \Twig_Environment(new PuliTemplateLoader($repo));
    $twig->addExtension(new PuliExtension($repo));

As you see in this code snippet, you need to pass the Puli repository to
both the loader and the extension. If you don't know how to create that, read
the :doc:`../getting-started` guide.

Usage in Twig
-------------

Using Puli in Twig is straight-forward: Use Puli paths wherever you would
usually use a file path. For example:

.. code-block:: jinja

    {% extends '/acme/blog/views/layout.html.twig' %}

    {% block content %}
        {# ... #}
    {% endblock %}

Contrary to Twig's default behavior, you can also refer to templates using
relative paths:

.. code-block:: jinja

    {% extends 'layout.html.twig' %}

    {% block content %}
        {# ... #}
    {% endblock %}

.. _Composer: https://getcomposer.org
.. _Twig templating engine: http://twig.sensiolabs.org
