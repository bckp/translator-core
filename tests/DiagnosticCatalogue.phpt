<?php

declare(strict_types=1);

require __DIR__ . '/bootstrap.php';

use Bckp\Translator\CatalogueBuilder;
use Bckp\Translator\Diagnostics\DiagnosticCatalogue;
use Bckp\Translator\Diagnostics\Diagnostics;
use Bckp\Translator\Exceptions\TranslatorException;
use Bckp\Translator\MessageCatalogue;
use Bckp\Translator\PhpCache\PhpCatalogueStorage;
use Bckp\Translator\Plural;
use Bckp\Translator\PluralMessage;
use Bckp\Translator\Sources\StaticSource;
use Bckp\Translator\Storage\MemoryStorage;
use Bckp\Translator\Translator;
use Tester\Assert;

$messages = new MessageCatalogue()
	->add('hello', 'Hello')
	->add('empty', '')
	->add('zero', '0')
	->add('format', 'Hello %s')
	->add('options', new PluralMessage(zero: 'off', one: 'on'))
	->add('emptyPlural', new PluralMessage(one: '', other: '0'));

foreach ([new MemoryStorage(), new PhpCatalogueStorage(TEMP_DIR . '/diagnostic-catalogues')] as $storage) {
	$builder = new CatalogueBuilder($storage, 'cs')->addSource('app', new StaticSource($messages, 'v1'));
	$catalogue = $builder->compile();
	Assert::same(Plural::Few, $catalogue->missingPluralForm('options', 3));
	Assert::same(Plural::Other, $catalogue->missingPluralForm('options'));
	Assert::same(Plural::Other, $catalogue->missingPluralForm('options', 'Ada'));
	Assert::same(Plural::Few, $catalogue->missingPluralForm('options', ' 3 '));
	Assert::same(Plural::Few, $catalogue->missingPluralForm('options', 3.9));
	Assert::null($catalogue->missingPluralForm('options', 0));
	Assert::null($catalogue->missingPluralForm('options', 1));
	Assert::null($catalogue->missingPluralForm('emptyPlural', 1));
	Assert::null($catalogue->missingPluralForm('emptyPlural', 8));
	Assert::null($catalogue->missingPluralForm('hello', 3));
	Assert::null($catalogue->missingPluralForm('missing', 3));

	$diagnostics = new Diagnostics();
	$debug = new DiagnosticCatalogue($catalogue, $diagnostics);
	Assert::same($catalogue->locale, $debug->locale);
	Assert::same($catalogue->build, $debug->build);
	Assert::same($catalogue->versions, $debug->versions);
	Assert::same('cs', $diagnostics->getLocale());
	Assert::same(Plural::Few, $debug->plural(3));
	Assert::true($debug->has('options'));
	Assert::false($debug->has('missing'));
	Assert::same(Plural::Few, $debug->missingPluralForm('options', 3));
	Assert::same(0, $diagnostics->getWarnings()->count());
	Assert::same(0, $diagnostics->getUntranslated()->count());
	Assert::same('on', $debug->get('options', 3));
	Assert::same('Plural form not defined. (message: options, form: few)', $diagnostics->getWarnings()->first());
	Assert::null($debug->get('missing', 3));
	Assert::same('missing', $diagnostics->getUntranslated()->first());
	Assert::same('', $debug->get('empty'));
	Assert::same('0', $debug->get('zero'));
	Assert::same('', $debug->get('emptyPlural', 1));
	Assert::same('0', $debug->get('emptyPlural', 8));
	Assert::same(1, $diagnostics->getWarnings()->count());
	Assert::same(1, $diagnostics->getUntranslated()->count());

	$firstDiagnostics = new Diagnostics();
	$secondDiagnostics = new Diagnostics();
	$plain = new Translator($catalogue);
	$first = new Translator($catalogue, $firstDiagnostics);
	$second = new Translator($catalogue, $secondDiagnostics);
	Assert::same('on', $plain->translate('options', 8));
	Assert::same('missing', $plain->translate('missing'));
	Assert::same('on', $first->translate('options', 3));
	Assert::same('missing', $first->translate('missing', 3));
	Assert::same('Hello Ada', $first->translate('format', 'Ada'));
	Assert::same(1, $firstDiagnostics->getWarnings()->count());
	Assert::same(1, $firstDiagnostics->getUntranslated()->count());
	Assert::same(0, $secondDiagnostics->getWarnings()->count());
	Assert::same(0, $secondDiagnostics->getUntranslated()->count());
	Assert::same('on', $second->translate('options'));
	Assert::same('Plural form not defined. (message: options, form: other)', $secondDiagnostics->getWarnings()->first());

	$replacement = new CatalogueBuilder($storage, 'cs', namespace: 'replacement')
		->addSource('app', new StaticSource(new MessageCatalogue()
			->add('hello', 'Updated')
			->add('options', new PluralMessage(other: 'replacement')), 'v2'))->compile();
	$first->replaceCatalogue($replacement);
	Assert::same('Updated', $first->translate('hello'));
	Assert::same('replacement', $first->translate('options', 1));
	Assert::true($firstDiagnostics->getWarnings()->contains('Plural form not defined. (message: options, form: one)'));
	Assert::same('new-missing', $first->translate('new-missing'));
	Assert::same(2, $firstDiagnostics->getWarnings()->count());
	Assert::same(2, $firstDiagnostics->getUntranslated()->count());
	Assert::same(1, $secondDiagnostics->getWarnings()->count());
	Assert::same('Hello', $second->translate('hello'));
	$plain->replaceCatalogue($replacement);
	Assert::same('Updated', $plain->translate('hello'));
	Assert::same('replacement', $plain->translate('options', 1));
	Assert::same(2, $firstDiagnostics->getWarnings()->count());
	$english = new CatalogueBuilder($storage, 'en')->compile();
	Assert::exception(static fn() => $first->replaceCatalogue($english), TranslatorException::class);
	Assert::same('Updated', $first->translate('hello'));
	Assert::same('cs', $first->getLocale());
	Assert::same('cs', $firstDiagnostics->getLocale());
}

$path = TEMP_DIR . '/previous-format';
mkdir($path);
$key = 'same-key';
$marker = $path . '/executed';
file_put_contents($path . '/' . hash('sha256', '4:' . $key) . '.php', '<?php file_put_contents(' . var_export($marker, true) . ', "old"); return null;');
Assert::null(new PhpCatalogueStorage($path)->load($key));
Assert::false(is_file($marker));
