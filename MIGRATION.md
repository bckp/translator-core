# Migrating from 2.x to 3.0

3.0 is a breaking redesign and requires PHP 8.4 or newer, including its plugins and the Nette adapter. Upgrade the Nette adapter together with the core.

## Source and storage separation

| 2.x | 3.0 |
| --- | --- |
| `new CatalogueBuilder($plural, $path, $locale)` | `new CatalogueBuilder($storage, $locale, $plural)` |
| Core writes cache files | `PhpCatalogueStorage` plugin writes compiled PHP |
| `addFile()` | `addSource($id, new NeonSource($directory))` |
| `addDynamic()` with a mutable array | `CallbackSource` or `TranslationSource`, returning `MessageCatalogue` |
| Optional `addCheckCallback()` | Mandatory `getVersion($locale): string` |
| `addCompileCallback()` mutates an array | Transform typed messages inside the loader, or register a later overriding source |
| Debug compilation repeatedly checks | Once on catalogue initialization per builder scope |
| Array plural variants | `new PluralMessage(one: '...', other: '...')` |
| `PluralProvider::getPlural()` callable | `PluralProvider::getRule()` returning `PluralRule` |

The core only requires PHP. NEON parsing and compiled PHP storage are separate Composer packages with a dependency on core 3.x. There is no compatibility shim for the old event/file API.

The NEON source is maintained in the separate `bckp/translator-neon` repository. Its Composer package name and `Bckp\Translator\Neon` namespace are unchanged.

A NEON source accepts directories and discovers only the requested locale at runtime. When moving `addFile()` registrations, keep source order explicit. Later sources override earlier ones. Keys retain the `{filename-prefix}.{message-key}` convention.

Every source has a stable ID and an opaque string version. Use a new ID or namespace when changing the meaning of a source. Change the version or explicitly force a rebuild after modifying its data.

## Typed data

Source loaders return `MessageCatalogue`. Entries are `string|PluralMessage`; raw arrays, numbers, booleans and nested maps are rejected. At an external boundary such as a database or NEON decoder, validate data and convert it into these objects.

Core translation parameters are `string|int|float`. The Nette adapter retains Nette's `mixed` parameter contract and normalizes scalar, null and `Stringable` values; arrays and other objects throw.

The normalizer receives a string and must return a string. Invalid formatting throws `TranslatorException` with the original `ValueError` as its cause. Empty strings and `"0"` are no longer treated as missing translations.

## Cache lifecycle

Production verification defaults to 1% at first use of each locale. Debug always verifies at that point. Repeated translations and repeated `compile()` calls on the same builder do not verify.

For long-running workers, use a new builder/provider scope per job, or call `forceRecompile()` explicitly. Probability does not provide a deadline for refresh.

The core builder's `forceRecompile()` returns a replacement catalogue. Pass it to the live translator's `replaceCatalogue()`. The Nette adapter handles that update and offers `forceRecompile(?string $locale = null)` itself.

Do not reuse 2.x cache files. The 3.0 PHP storage uses new keys and content-specific compiled classes. The storage directory must be writable by PHP.

## Nette

Replace `path` with a named `sources` map in the Nette configuration. Resolvers now receive `StringList` instead of an untyped array. See the adapter's README for DI configuration and manual refresh examples.
