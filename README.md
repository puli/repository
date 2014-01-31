Puli - A PHP Resource Manager
=============================

Puli manages the file resources of you PHP project and provides access to these
resources through a unified naming system. Puli manages files in *repositories*,
where you map them to a path:

```php
use Webmozart\Puli\Configuration\RepositoryConfiguration;

$config = new RepositoryConfiguration('/path/to/project');
$config->setPath('/webmozart/puli', 'resources/assets/*');
$config->setPath('/webmozart/puli/trans', 'resources/trans');
```

Currently, Puli only provides a repository implementation that caches the
repository paths in PHP files. Pass the path where these files are stored when
you call the `dump()` method of the `PhpRepositoryDumper`:

```php
use Webmozart\Puli\Dumper\PhpRepositoryDumper;

$dumper = new PhpRepositoryDumper();
$dumper->dump($config, '/path/to/cache/resources');
```

Then create a `PhpDumpRepository` at this location, which lets you locate the
paths of the files in your repository:

```php
use Webmozart\Puli\Repository\PhpDumpRepository;

$repo = new PhpDumpRepository('/path/to/cache/resources');

echo $repo->getResource('/webmozart/puli/css/style.css')->getPath();
// => /path/to/project/resources/assets/css/style.css

echo $repo->getResource('/webmozart/puli/trans/en.xlf')->getPath();
// => /path/to/project/resources/trans/en.xlf
```
