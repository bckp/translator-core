# Bckp Translator 3.0

A small PHP 8.4+ translator with versioned sources and a compiled hot path. The core has no filesystem or framework dependency. It accepts typed translation sources and delegates cache persistence to a storage interface.

This is the `nextgen` development branch for the breaking 3.0 release.

## Packages

| Package | Responsibility |
| --- | --- |
| `bckp/translator-core` | Typed messages, source contracts, version checks, catalogue building and translation |
| `bckp/translator-neon` | NEON source plugin, in its [own repository](https://github.com/bckp/translator-neon) |
| `bckp/translator-php-cache` | Compiled PHP storage plugin, in [`packages/php-cache`](packages/php-cache) |
| `bckp/translator-nette` | Nette DI, locale resolution, Latte and Tracy integration |

The plugins are separate Composer packages. The PHP-cache plugin is developed in `packages/php-cache` and installed locally through Composer. The NEON plugin has its own repository, tests and quality checks; it is not required to develop or test the core.

```sh
COMPOSER_ROOT_VERSION=3.0.x-dev composer install
composer tests
composer phpstan
composer phpcs
composer benchmark
```

PHPStan runs at level 8. Source code uses native types, strict types and attributes without docblocks. PHP CS Fixer uses the PER Coding Style 3.0 preset with tab indentation and PHP 8.4 migration rules. EditorConfig also selects tabs for PHP files.

## Quick start

```php
<?php

declare(strict_types=1);

use Bckp\Translator\CatalogueBuilder;
use Bckp\Translator\MessageCatalogue;
use Bckp\Translator\PhpCache\PhpCatalogueStorage;
use Bckp\Translator\PluralMessage;
use Bckp\Translator\Sources\StaticSource;
use Bckp\Translator\Translator;

$messages = (new MessageCatalogue())
    ->add('messages.welcome', 'Vítejte')
    ->add('messages.hello', 'Ahoj %s')
    ->add('messages.people', new PluralMessage(
        zero: 'žádný člověk',
        one: '%d člověk',
        few: '%d lidé',
        other: '%d lidí',
    ));

$builder = (new CatalogueBuilder(
    storage: new PhpCatalogueStorage(__DIR__ . '/temp/translations'),
    locale: 'cs',
    namespace: 'my-application',
))
    ->addSource('application', new StaticSource($messages, 'release-42'))
    ->setCheckProbability(0.01);

$translator = new Translator($builder->compile());
echo $translator->translate('messages.hello', 'Ada');
echo $translator->translate('messages.people', 4);
```

A `MessageCatalogue` only accepts a string or a `PluralMessage`. A plural message has named, nullable string variants and must contain at least one variant. Empty translations and the key/value `"0"` are valid. A missing key returns the original message; a missing plural variant falls back to another defined variant and can be reported through diagnostics.

A runtime `Catalogue::get(string $key, float|int|string|null $number = null): ?string` returns the selected text, including its fallback, or `null` for an unknown key. Ordinary messages ignore the number. Only plural entries inspect the quantity: numeric values are converted to integers for the locale's rule, while omitted or nonnumeric values select `Other`. The translator forwards the first formatting parameter unchanged.

`Catalogue::missingPluralForm()` accepts the same key and quantity and returns the missing `Plural` category, or `null` for a defined form, ordinary text or unknown key. It does not report anything or mutate the catalogue. When diagnostics are enabled, the translator uses a `DiagnosticCatalogue` wrapper to report unknown keys and missing forms. The production translation path contains no diagnostics checks.

Sources merge in registration order: the last source wins for an identical key. `StaticSource` snapshots its input. Storage also snapshots the definition, so later edits to source messages do not change a live catalogue.

## Callback sources and mandatory versions

Register sources before calling `compile()`:

```php
<?php

declare(strict_types=1);

use Bckp\Translator\MessageCatalogue;
use Bckp\Translator\Sources\CallbackSource;

$revision = 'initial';
$text = 'Hello';

$source = new CallbackSource(
    loader: static function (string $locale) use (&$text): MessageCatalogue {
        return (new MessageCatalogue())->add('messages.hello', $text);
    },
    version: static function (string $locale) use (&$revision): string {
        return $revision;
    },
);

$builder->addSource('database', $source);
```

Both callbacks receive the normalized locale. The loader must return `MessageCatalogue`; the version callback must return a string. Invalid callback results throw an exception.

A version is an opaque string compared for equality, never ordered. Use a content hash, database revision, timestamp converted to string, deployment identifier, or a constant for immutable data. Empty strings and `"0"` are valid versions too. There is no unversioned source.

For a native contract with statically checked callback signatures, implement `Bckp\Translator\Interfaces\TranslationSource`:

```php
interface TranslationSource
{
    public function getVersion(string $locale): string;

    public function __invoke(string $locale): MessageCatalogue;
}
```

Keep source constructors free of IO where practical. Nette delays source construction until a catalogue actually needs a version check or reload.

## Checking and rebuilding

| Situation | Version callbacks | Load callbacks |
| --- | --- | --- |
| Missing cache | Always | Always |
| Warm cache, skipped check | Never | Never |
| Warm cache, selected check, unchanged versions | Once per source | Never |
| Warm cache, changed versions | Once per source | Once per source |
| `forceRecompile()` | Always | Always, even with identical versions |
| Subsequent `compile()` calls on the same builder | Never | Never |
| `translate()` | Never | Never |

`setCheckProbability()` accepts a finite value from 0 to 1; the default is `0.01`. The decision happens once when the locale's catalogue is first initialized. `setDebugMode(true)` always checks at that point, regardless of probability. It does not check on every individual translation.

At a 1% probability, each warm request using a locale has a 1% chance of checking it. Detection time depends on traffic; this is not a fixed refresh interval. A value of 0 disables automatic verification while still building missing caches.

Create a new builder scope per request or job in long-running workers. A builder holds its catalogue until explicitly forced.

```php
$builder->setDebugMode(true);

$catalogue = $builder->forceRecompile();
$translator->replaceCatalogue($catalogue);
```

A core translator uses only its current catalogue. `replaceCatalogue()` updates an existing translator and requires the same locale. The Nette translator offers `forceRecompile()` directly and updates all existing references automatically.

A failed source load or storage write throws and leaves the existing in-memory catalogue in place. The PHP storage publishes a complete cache file by atomic rename.

## Cache identity

The stable storage key includes format version, namespace, normalized locale, plural rule and ordered source IDs. Source versions are stored in the compiled catalogue, so a skipped check does not need to access the sources.

IDs identify the logical sources. When changing a source's configuration while keeping the same ID, update its version, change the namespace, or force a rebuild. A request that skips verification deliberately uses the existing cache. Use separate namespaces when sharing storage between applications or tenants.

Sources cannot be added or changed after a builder initializes its catalogue.

## NEON files

Install/use the NEON plugin and register it as an ordinary source:

```php
use Bckp\Translator\Neon\NeonSource;

$builder->addSource('files', new NeonSource(__DIR__ . '/locales'));
```

Files follow `{resource}.{locale}.neon`, for example `messages.cs.neon`:

```neon
welcome: 'Vítejte'
hello: 'Ahoj %s'
people:
    zero: 'žádný člověk'
    one: '%d člověk'
    few: '%d lidé'
    other: '%d lidí'
```

Register the source before calling `compile()`. The file prefix produces keys such as `messages.welcome`. The plugin computes a content version automatically, including added and removed files. A configured constant version can avoid hashing for immutable release assets. See the [plugin documentation](https://github.com/bckp/translator-neon/tree/nextgen).

## Storage and diagnostics

`MemoryStorage` is an alternative without disk persistence. Implement `Interfaces\CatalogueStorage` to provide another backend; it receives a typed `CatalogueDefinition` and returns a `Catalogue`.

Compiled PHP catalogues return ordinary texts directly from a generated `match` and keep plural strings in a constant map. They create no `PluralMessage` objects at runtime and inspect quantities only for plural entries. Translation does no source IO, random sampling or version checks. `composer benchmark-simple` measures plain, formatted and plural translations and asserts that source call counters do not change. See the [benchmark results](benchmarks/README.md) for the initial 2.x comparison.

The PHP cache plugin copies the selected plural rule's body from `PluralProvider` at compilation. The compiled catalogue then selects plurals directly, without calling the provider. Rebuilding captures any later change to that rule.

An optional `Diagnostics` implementation receives missing messages, plural warnings and `CatalogueStatus` records containing the stored versions and whether initialization checked/recompiled the catalogue. Rendering diagnostics does not invoke sources.

The built-in plural provider retains Czech, English-style and invariant rules and selects by the base language of normalized locales such as `cs-cz`. Override `getRule()` to choose among these rules. A different rule implementation requires extending the catalogue/storage contract.

See [MIGRATION.md](MIGRATION.md) for the 2.x API changes.

## Quality tooling

GitHub Actions runs Nette Tester on PHP 8.4 and 8.5, PHPStan level 8, PER 3 with tabs, and `composer audit`. Existing coverage checks remain in place. The audit fails on known vulnerability advisories and reports abandoned development dependencies; PHPBench currently requires the abandoned Doctrine annotations package even when benchmarks use attributes.

### API compatibility

Roave Backward Compatibility Check has isolated dependencies:

```sh
composer bc-install
composer bc-check
```

The check compares committed HEAD against the highest stable `3.x.y` or `v3.x.y` tag. Prereleases and older majors are excluded. Until the first stable 3.x release, it explicitly reports that no baseline exists. CI also compares HEAD with itself to verify the checker and local dependency resolution before that release.

Use `composer bc-check -- <tag-or-commit>` for an explicit baseline. Keep sibling repositories available as described above; they are linked into the checker's temporary directory. Temporary comparisons are stored under the ignored `temp/` directory. Uncommitted changes are not checked.

### Dependency updates

`renovate.json` groups development dependencies and GitHub Actions updates, checks the default branch and `nextgen`, and schedules updates before 07:00 Europe/Prague on Mondays. Automatic merging is disabled. CI validates this configuration using the official Renovate validator.

The hosted Renovate GitHub App must be enabled for the repository. It discovers configuration on the default branch, so this file must also be present there before activation while `nextgen` is a separate development branch.

### Mutation testing

Infection is deferred because it does not directly support Nette Tester. Adoption will be considered together with a possible move to PHPUnit.

### Performance

`composer benchmark` runs PHPBench with attributes, warmed compiled PHP catalogues, OPcache enabled and JIT/Xdebug disabled. It verifies that translations never call a source loader or version callback. See [benchmark instructions](benchmarks/README.md) for storing and comparing results. CI uploads results separately for PHP 8.4 and 8.5; timing differences are informational.
