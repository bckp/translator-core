# Compiled PHP catalogue storage

`Bckp\Translator\PhpCache\PhpCatalogueStorage` implements `CatalogueStorage` and is included in `bckp/translator-core` 3.x. PHP 8.4+ and the tokenizer extension are required.

Its implementation lives in [`src/PhpCache`](../src/PhpCache). Install only `bckp/translator-core`; the storage needs no separate Composer package or repository.

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

Cache file names are hashes of the storage format version and the builder's stable storage key. Source versions and build metadata are embedded in the compiled catalogue. Generated class names are content hashes, allowing a forced rebuild to replace a catalogue even with an unchanged source version in the same process. Older files are ignored when the storage format changes.

The compiled class returns ordinary texts directly from a generated `match` and stores plural strings in `private const array Plurals`. `get()` returns `string|null` and takes an optional quantity without formatting the result. Only plural entries inspect the quantity: numeric values are converted to integers for the locale's rule, while omitted or nonnumeric values select `Other`. Missing forms use the first defined variant in this order: `other`, `many`, `few`, `two`, `one`, `zero`. A separate `missingPluralForm()` lookup supplies the missing category to the core's diagnostic wrapper; production reads do not check diagnostics. No `PluralMessage` objects are created by the compiled file. Text and metadata are exported as PHP literals. OPcache entries are invalidated after replacement. Concurrent writers publish complete catalogues; readers can observe either completed revision.

Generated `match` methods use more OPcache memory than the 2.x static-array catalogue. See the [core benchmark comparison](../benchmarks/README.md) for measured translation times and generated-script memory use.

Use a directory dedicated to generated cache files and writable by PHP. Like any executable PHP cache, it must not be writable by untrusted users. No source data is loaded while using a warm cache whose verification was skipped.

Use core's `MemoryStorage` for an in-process backend without filesystem persistence.

At compilation, the storage uses reflection and PHP tokens to copy the selected rule's method body from `PluralProvider` into the generated `plural()` method. `PluralProvider` remains the rule's single definition. The source file is read only when compiling; cached plural selection makes no provider call and performs no source IO. Changing the provider after compilation requires a rebuild to update the embedded rule.

Format 6 excludes earlier caches so the embedded rule is used after upgrading. The source body participates in the generated class hash, allowing multiple compiled revisions to coexist in one process.
