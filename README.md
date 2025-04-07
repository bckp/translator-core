Bckp\Translator
====================

[![Downloads this Month](https://img.shields.io/packagist/dm/bckp/translator-core.svg)](https://packagist.org/packages/bckp/translator-core)
[![Build Status](https://github.com/bckp/translator-core/actions/workflows/tests.yaml/badge.svg)](https://github.com/bckp/translator-core/actions/workflows/tests.yaml)
[![Scrutinizer Code Quality](https://scrutinizer-ci.com/g/bckp/translator-core/badges/quality-score.png?b=main)](https://scrutinizer-ci.com/g/bckp/translator-core/?branch=main)
[![Latest Stable Version](https://poser.pugx.org/bckp/translator-core/v/stable)](https://packagist.org/packages/bckp/translator-core)
[![License](https://img.shields.io/badge/license-New%20BSD-blue.svg)](https://github.com/nette/application/blob/master/license.md)

Simple and fast PHP translator

Usage
-----
For each language, we create Catalogue, that will compile PHP cache file with translations.
```php
$catalogue = new Catalogue(new PluralProvider(), './path/to/cache', 'cs');
$catalogue->addFile('./path/to/locales/errors.cs.neon');
$catalogue->addFile('./path/to/locales/messages.cs.neon');

// Enable debug mode, disabled by default
$catalogue->setDebugMode(true);

$compiledCatalogue = $catalogue->compile();
$translator = new Translator($compiledCatalogue);

$translator->translate('errors.error.notFound'); // Will output "Soubor nenalezen"
$translator->translate('messages.plural', 4); // Will output "4 lidé"
$translator->translate('messages.withArgs', 'Honza', 'poledne'); // Will output "Ahoj, já jsem Honza, přeji krásné poledne"
$translator->translate('messages.withArgsRev', 'Honza', 'poledne'); // Will output "Krásné poledne, já jsem Honza"
```

You can add your own source of text by callback

```php
$catalogue = new Catalogue(new PluralProvider(), './path/to/cache', 'cs');
$catalogue->addDynamic('errors', function(array &$messages, string $resource, string $locale){
    $messages['common'] = 'Common error translation';
    $messages['critical'] = $this->database->fetchAll('translations')->where('resource = ? and locale = ?', $resource, $locale);
});
$catalogue->addFile('./path/to/locales/messages.cs.neon');
// if you add new file errors.cs.neon, it will be overwritten by dynamic, as they is processed later

// Enable debug mode, disabled by default
$catalogue->setDebugMode(true);

// You can even add events for onCheck
// $timestamp contains timestamp of last file generation
// but remember, this will called only on debug mode!
$catalogue->addCheckCallback(function(int $timestamp){
    if ($timestamp < $this->database->fetchSingle('select last_update from settings where caption = ?', 'translations')){
        throw new BuilderException('rebuild required');
    }
});

// And events for onCompile
// this will occur when app have prepared all translations into single
// big array, and you want to modify it
$catalogue->addCompileCallback(function(array &$messages, string $locale){
    // in messages, we have all the translations
    $messages['errors.common'] = 'Modify common error translation';
});

$compiledCatalogue = $catalogue->compile();
$translator = new Translator($compiledCatalogue);

$translator->translate('errors.common'); // Will output "Modify common error translation"
```

Debug mode will made translator slower, it will check every time you call compile() if some of language files did change or not, and if they do, automaticly recompile cache, this is Good for development, but BAD on production mode.

Or you can use TranslatorProvider
```php
$catalogue = new Catalogue(new PluralProvider(), './path/to/cache', 'cs');
$catalogue->addFile('./path/to/locales/errors.cs.neon');
$catalogue->addFile('./path/to/locales/messages.cs.neon');

$provider = new TranslatorProvider(['cs','en']);
$provider->addCatalogue('cs', $catalogue);

$translator = $provider->getTranslator('cs');
```

Great about this approach is, if you use more then one language, and switch between them, translator provider will compile catalogue only on first use, then use the compiled one.


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

Diagnostics
-----------
You can use simple diagnostics (or implement one), if you want to know, what is wrong with your translations, this is primary for framework integration (like Nette one).

