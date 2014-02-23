Locating Files with Puli
========================

[![Build Status](https://travis-ci.org/webmozart/puli.png?branch=master)](https://travis-ci.org/webmozart/puli)
[![Scrutinizer Quality Score](https://scrutinizer-ci.com/g/webmozart/puli/badges/quality-score.png?s=f1fbf1884aed7f896c18fc237d3eed5823ac85eb)](https://scrutinizer-ci.com/g/webmozart/puli/)
[![Code Coverage](https://scrutinizer-ci.com/g/webmozart/puli/badges/coverage.png?s=5d83649f6fc3a9754297da9dc0d997be212c9145)](https://scrutinizer-ci.com/g/webmozart/puli/)
[![SensioLabsInsight](https://insight.sensiolabs.com/projects/728198dc-dc0f-4bab-b5c0-c0b4e2a55bce/mini.png)](https://insight.sensiolabs.com/projects/728198dc-dc0f-4bab-b5c0-c0b4e2a55bce)
[![Latest Stable Version](https://poser.pugx.org/webmozart/puli/v/stable.png)](https://packagist.org/packages/webmozart/puli)
[![Total Downloads](https://poser.pugx.org/webmozart/puli/downloads.png)](https://packagist.org/packages/webmozart/puli)
[![Dependency Status](https://www.versioneye.com/php/webmozart:puli/1.0.0/badge.png)](https://www.versioneye.com/php/webmozart:puli/1.0.0)

Latest release: [1.0.0-alpha3](https://packagist.org/packages/webmozart/puli#1.0.0-alpha3)

PHP >= 5.3.9

Puli returns the absolute file paths of the files (*resources*) in your PHP
project. You can refer to those files through simple names that look very
much like file paths:

```php
echo $locator->get('/webmozart/puli/css/style.css')->getRealPath();
// => /path/to/resources/assets/css/style.css
```

Like this, you can use short and memorable paths whenever you need to
reference a file in your project, for example:

```yaml
# config.yml
import: /webmozart/puli/config/config.yml
```

The structure of these file paths can, of course, be configured by yourself.
You will learn later in this document how to do so.

Installation
------------

You can install Puli with [Composer]:

```json
{
    "require": {
        "webmozart/puli": "~1.0@dev"
    }
}
```

Run `composer install` or `composer update` to install the library. At last, include Composer's generated autoloader and you're ready to start:

```php
require_once __DIR__.'/vendor/autoload.php';
```

Documentation
-------------

1. [Basic Usage]: Teaches you about the basic use of Puli.
2. [Advanced Usage]: Teaches you about advanced features and extension points
   in Puli.

Bundled Extensions
------------------

The following extensions are provided in the [`Webmozart\Puli\Extension`]
namespace:

Extension | Description                                                                        | Stability | Documentation
--------- | ---------------------------------------------------------------------------------- | --------- | -----------------------------------
Assetic   | You can create [Assetic] assets with Puli paths using the bundled asset factory.   | alpha     | -
Symfony   | Puli provides a file locator for the Symfony [Config] and [HttpKernel] components. | alpha     | [Documentation](doc/ext-symfony.md)
Twig      | The [Twig] extension lets you access templates via Puli paths.                     | alpha     | [Documentation](doc/ext-twig.md)

Tool Integration
----------------

Puli is also integrated into several tools via external libraries:

Tool     | Description                                                                             | Version
-------- | --------------------------------------------------------------------------------------- | ---------------
Composer | The [Puli plugin for Composer] builds resource locators from composer.json definitions. | 1.0.0-alpha1
Pash     | The [Pash shell] lets you interactively browse Puli repositories.                       | 1.0.0-dev
Symfony  | The [Puli bundle] integrates Puli with the [Symfony full-stack framework].              | 1.0.0-dev

[Composer]: https://getcomposer.org
[Basic Usage]: doc/1-basic-usage.md
[Advanced Usage]: doc/2-advanced-usage.md
[Composer plugin]: https://github.com/webmozart/composer-puli-plugin
[Puli plugin for Composer]: https://github.com/webmozart/composer-puli-plugin
[Puli extension for Twig]: https://github.com/webmozart/twig-puli-extension
[Puli bridge]: https://github.com/webmozart/symfony-puli-bridge
[Puli bundle]: https://github.com/webmozart/symfony-puli-bundle
[Pash shell]: https://github.com/webmozart/pash
[Symfony full-stack framework]: http:/symfony.com
[Twig]: http://twig.sensiolabs.org
[Config]: http://symfony.com/doc/current/components/config/introduction.html
[HttpKernel]: http://symfony.com/doc/current/components/http_kernel/introduction.html
[Assetic]: https://github.com/kriswallsmith/assetic
[`Webmozart\Puli\Extension`]: src/Extension
