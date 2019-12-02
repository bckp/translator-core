Bckp\Translator
====================

[![Downloads this Month](https://img.shields.io/packagist/dm/bckp/translator-core.svg)](https://packagist.org/packages/bckp/translator-core)
[![Build Status](https://travis-ci.org/bckp/translator-core.svg?branch=master)](https://travis-ci.org/bckp/translator-core)
[![Coverage Status](https://coveralls.io/repos/github/bckp/translator-core/badge.svg?branch=master)](https://coveralls.io/github/bckp/translator-core?branch=master)
[![Latest Stable Version](https://poser.pugx.org/bckp/translator-core/v/stable)](https://packagist.org/packages/bckp/translator-core)
[![License](https://img.shields.io/badge/license-New%20BSD-blue.svg)](https://github.com/nette/application/blob/master/license.md)
[![Scutinizer](https://img.shields.io/scrutinizer/quality/g/bckp/translator-core)](https://img.shields.io/scrutinizer/quality/g/bckp/translator-core)

Simple and fast PHP translator

Usage
-----
For each language, we create Catalogue, that will compile PHP cache file with translations.
```php
$catalogue = new Catalogue(ew PluralProvider(), './path/to/cache', 'cs');
$catalogue->addFile('./path/to/locales/errors.cs.neon');
$catalogue->addFile('./path/to/locales/messages.cs.neon');

// Enable debug mode, disabled by default
$catalogue->setDebugMode(true);

$compiledCatalogue = $catalogue->compile();
$translator = new Translator($compiledCatalogue);

$translator->translate('errors.error.notFound'); // Will output "Soubor nenalezen"
$translator->translate(['messages.plural', 4]); // Will output "4 lidé"
$translator->translate('messages.withArgs', 'Honza', 'poledne'); // Will output "Ahoj, já jsem Honza, přeji krásné poledne"
$translator->translate('messages.withArgsRev', 'Honza', 'poledne'); // Will output "Krásné poledne, já jsem Honza"
```

Debug mode will made translator slower, it will check every time you call compile() if some of language files did change or not, and if they do, automaticly recompile cache, this is Good for development, but BAD on production mode.


Translation file format
-----------------------
Translation files are written in NEON format. Plural strings are in ARRAY, otherwise STRING.
```neon
welcome: 'Vítejte'
withArgs: 'Ahoj, já jsem %s, přeji krásné %s'
withArgsRev: 'Krásné %2$s, já jsem %1$s'
plural:
	zero: 'žádný člověk'
	one: 'jeden člověk'
	few: '%d lidé'
	other: '%d lidí'
next: 'This is next translation'
```
If you want more structure, you can use DOT as character, so lets say we want 3 error messages, we will create structure like this
```neon
error.notFound: 'Soubor nenalezen'
error.notMatch: 'Soubor neodpovídá zadání'
error.typeMismatch: 'Neplatný typ souboru'
```

