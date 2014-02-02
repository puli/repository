Puli - Sane Resource Management for PHP
=======================================

Puli manages the file resources of your PHP project and provides access to these
resources through a unified naming system. Puli manages files in *repositories*,
where you map them to a path:

```php
use Webmozart\Puli\Repository\ResourceRepository;

$repo = new ResourceRepository();
$repo->add('/webmozart/puli', '/path/to/resources/assets/*');
$repo->add('/webmozart/puli/trans', '/path/to/resources/trans');
```

You can then locate the files using the `get()` method:

```php
echo $repo->get('/webmozart/puli/css/style.css')->getPath();
// => /path/to/project/resources/assets/css/style.css

echo $repo->get('/webmozart/puli/trans/en.xlf')->getPath();
// => /path/to/project/resources/trans/en.xlf
```

Puli allows to dump optimized resource locators. Currently, Puli only provides
one locator implementation that caches the repository paths in PHP files. Pass
the path where these files are stored when you call the `dumpLocator()` method
of the `PhpResourceLocatorDumper`:

```php
use Webmozart\Puli\LocatorDumper\PhpResourceLocatorDumper;

$dumper = new PhpResourceLocatorDumper();
$dumper->dumpLocator($repo, '/path/to/cache');
```

Then create a `PhpResourceLocator` at this location, which lets you locate the
paths of the files in your repository:

```php
use Webmozart\Puli\Locator\PhpResourceLocator;

$locator = new PhpResourceLocator('/path/to/cache');

echo $locator->get('/webmozart/puli/css/style.css')->getPath();
// => /path/to/project/resources/assets/css/style.css

echo $locator->get('/webmozart/puli/trans/en.xlf')->getPath();
// => /path/to/project/resources/trans/en.xlf
```
