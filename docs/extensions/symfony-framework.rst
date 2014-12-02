Using Puli with the Symfony Framework
=====================================

There are two ways of using Puli with the `Symfony framework`_:

* New projects can be started based on the `Symfony Puli Edition`_.
* Existing projects can be extended with the `Puli bundle`_.

Both ways are described in detail below.

Starting a Project from the Symfony Puli Edition
------------------------------------------------

A new project can be started based on the Symfony Puli Edition with `Composer`_.
`Install Composer`_ and enter the following command in a terminal:

.. code-block:: bash

    $ composer create-project puli/symfony-puli-edition /path/to/project "~2.5@dev"

.. tip::

    To download the vendor files faster, add the ``--prefer-dist`` option at the
    end of any Composer command.

Composer will create a new project based on the Symfony Puli Edition in
`/path/to/project` with Symfony 2.5.

Read the `Installing and Configuring Symfony`_ to learn more about installing
Symfony distributions.

Installing the Puli Bundle
--------------------------

If you cannot or don't want to start off the Symfony Puli Edition, you need to
install the Puli bundle with `Composer`_. `Install Composer`_ and enter the
following command in a terminal:

.. code-block:: bash

    $ composer require "puli/symfony-puli-bundle:~1.0@dev"

.. note::

    Make sure that the "minimum-stability" setting is set to "dev" in
    composer.json, otherwise the installation will fail:

    .. code-block:: json

        {
            ...,
            "minimum-stability": "dev"
        }

This will download the bundle to your project.

When this command completes, run ``composer install`` to initialize the
Composer plugin for Puli:

.. code-block:: bash

    $ composer install

Now, enable the bundle by modifying ``AppKernel``:

.. code-block:: php

    // app/AppKernel.php

    // ...
    class AppKernel extends Kernel
    {
        // ...

        public function registerBundles()
        {
            $bundles = array(
                // ...,
                new Puli\Extension\Symfony\PuliBundle\PuliBundle(),
            );

            // ...
        }
    }

The bundle is now installed in your project.

Bundle Usage
------------

Configuration Files
~~~~~~~~~~~~~~~~~~~

With the bundle, you can load configuration files by Puli paths. This is mostly
needed when loading bundle routes in routing.yml or routing_dev.yml:

.. code-block:: yaml

    # routing_dev.yml
    _wdt:
        resource: /symfony/web-profiler-bundle/config/routing/wdt.xml
        prefix:   /_wdt

This entry will load all routes found under the Puli path
``/symfony/web-profiler-bundle/config/routing/wdt.xml``. Usually, the first two
directories of a Puli path correspond to the name of a Composer package. In this
example, the file ``config/routing/wdt.xml`` is loaded from the ``Resources``
directory in the package "symfony/web-profiler".

Read :doc:`symfony-config` if you want to learn more about using Puli with
Symfony configuration files.

Twig Templates
~~~~~~~~~~~~~~

With the bundle, it is possible to refer to Twig templates by Puli paths. This
is typically done in the controller when rendering a template:

.. code-block:: php

    // DemoController.php

    // ...
    class DemoController extends Controller
    {
        /**
         * @Route("/hello/{name}", name="_demo_hello")
         */
        public function helloAction($name)
        {
            return $this->render('/acme/demo-bundle/views/demo/hello.html.twig', array(
                'name' => $name,
            ));
        }

        // ...
    }

In this example, the template at the Puli path
``/acme/demo-bundle/views/demo/hello.html.twig`` is rendered.

Within Twig templates, you can also refer to other templates by Puli paths:

.. code-block:: html+jinja

    {# views/demo/hello.html.twig #}

    {% extends "/acme/demo-bundle/views/layout.html.twig" %}

    ...

This will let the ``hello.html.twig`` template extend the template
``/acme/demo-bundle/views/layout.html.twig``. Instead of passing the absolute
Puli path, it is usually more comfortable to pass relative paths instead:

.. code-block:: html+jinja

    {# views/demo/hello.html.twig #}

    {% extends "../layout.html.twig" %}

    ...

Read :doc:`twig` to learn more about the Puli extension for Twig.

.. _Symfony framework: http://symfony.com
.. _Symfony Puli Edition: https://github.com/puli/symfony-puli-edition
.. _Puli bundle: https://github.com/puli/symfony-puli-bundle
.. _Puli CLI: https://github.com/puli/puli-cli
.. _Puli Composer Plugin: https://github.com/puli/puli-composer-plugin
.. _Installing and Configuring Symfony: http://symfony.com/doc/current/book/installation.html
.. _Composer: https://getcomposer.org
.. _Install Composer: https://getcomposer.org/doc/00-intro.md
