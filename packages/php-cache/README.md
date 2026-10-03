# Compiled PHP catalogue storage

`bckp/translator-php-cache` implements core 3.x `CatalogueStorage` for PHP 8.4+.

This package lives in the core repository's `packages/php-cache` directory during `nextgen` development.

```php
<?php

declare(strict_types=1);

use Bckp\Translator\CatalogueBuilder;
use Bckp\Translator\PhpCache\PhpCatalogueStorage;

$builder = new CatalogueBuilder(
    storage: new PhpCatalogueStorage(__DIR__ . '/temp/translations'),
    locale: 'cs',
    namespace: 'my-application',
);
```

Construction performs no IO. Missing, unreadable or invalid cache files return a cache miss. A write creates the directory as needed, compiles typed messages into PHP, validates the temporary file, and publishes it by atomic rename. Failed writes throw and temporary files are cleaned up.

Cache file names are hashes of the builder's stable storage key. Source versions and build metadata are embedded in the compiled catalogue. Generated class names are content hashes, allowing a forced rebuild to replace a catalogue even with an unchanged source version in the same process.

The compiled class uses a private lookup map, shared prebuilt plural objects and a direct plural selector. Text and metadata are exported as PHP literals. OPcache entries are invalidated after replacement. Concurrent writers publish complete catalogues; readers can observe either completed revision.

Use a directory dedicated to generated cache files and writable by PHP. Like any executable PHP cache, it must not be writable by untrusted users. No source data is loaded while using a warm cache whose verification was skipped.

Use core's `MemoryStorage` for an in-process backend without filesystem persistence.
