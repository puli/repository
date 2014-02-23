Loading Twig Templates with Puli
================================

Puli provides an extension for the [Twig templating engine]. With this
extension, you can refer to template files through Puli paths:

```php
echo $twig->render('/acme/blog/views/show.html.twig');
```

Installation
------------

In order to activate the extension, create a new [`PuliLoader`] and register it
with Twig. The loader turns a Puli path into an absolute path when loading a
template. Then, create a new [`PuliExtension`] and add it to Twig. The extension
takes care that templates loaded by the [`PuliLoader`] are processed correctly.

```php
use Webmozart\Puli\Extension\Twig\PuliLoader;
use Webmozart\Puli\Extension\Twig\PuliExtension;

$twig = new \Twig_Environment(new PuliLoader($locator));
$twig->addExtension(new PuliExtension($locator));
```

As you see in this code snippet, you need to pass Puli's resource locator to
both the loader and the extension. If you don't know how to create that
locator, you can find more information in Puli's [main documentation].

Usage
-----

Using Puli in Twig is straight-forward: Use Puli paths wherever you would
usually use a file path. For example:

```twig
{% extends '/acme/blog/views/layout.html.twig' %}

{% block content %}
    {# ... #}
{% endblock %}
```

Contrary to Twig's default behavior, you can also refer to templates using
relative paths:

```twig
{% extends 'layout.html.twig' %}

{% block content %}
    {# ... #}
{% endblock %}
```

[Twig templating engine]: http://twig.sensiolabs.org
[main documentation]: ../README.md
[`PuliLoader`]: ../src/Extension/Twig/PuliLoader.php
[`PuliExtension`]: ../src/Extension/Twig/PuliExtension.php
