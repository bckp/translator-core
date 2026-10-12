<?php

declare(strict_types=1);

require __DIR__ . '/bootstrap.php';

use Bckp\Translator\CatalogueBuilder;
use Bckp\Translator\Diagnostics\DiagnosticCatalogue;
use Bckp\Translator\Diagnostics\Diagnostics;
use Bckp\Translator\MessageCatalogue;
use Bckp\Translator\PhpCache\PhpCatalogueStorage;
use Bckp\Translator\PluralMessage;
use Bckp\Translator\Sources\StaticSource;
use Bckp\Translator\Storage\MemoryStorage;
use Bckp\Translator\Translator;
use Tester\Assert;

$messages = new MessageCatalogue()
	->add('hello', 'Hello')
	->add('empty', '')
	->add('zero', '0')
	->add('0', 'Zero key')
	->add('people', new PluralMessage(zero: 'Nobody', one: '%d person', few: '%d people', other: '%d persons'))
	->add('options', new PluralMessage(zero: 'off', one: 'on'))
	->add('emptyPlural', new PluralMessage(one: '', other: '0'));

foreach ([new MemoryStorage(), new PhpCatalogueStorage(TEMP_DIR . '/catalogues')] as $storage) {
	$builder = new CatalogueBuilder($storage, 'cs')->addSource('app', new StaticSource($messages, 'v1'));
	$catalogue = $builder->compile();
	Assert::same('%d persons', $catalogue->get('people'));
	Assert::same('Nobody', $catalogue->get('people', 0));
	Assert::same('%d person', $catalogue->get('people', 1));
	Assert::same('%d people', $catalogue->get('people', 3));
	Assert::same('%d people', $catalogue->get('people', ' 3 '));
	Assert::same('%d people', $catalogue->get('people', 3.9));
	Assert::same('%d persons', $catalogue->get('people', 'Ada'));
	Assert::same('%d persons', $catalogue->get('people', 5));
	Assert::same('%d persons', $catalogue->get('people', -1));
	Assert::same('Hello', $catalogue->get('hello', 3));
	Assert::same('', $catalogue->get('empty'));
	Assert::same('0', $catalogue->get('zero'));
	Assert::same('Zero key', $catalogue->get('0'));
	Assert::same('', $catalogue->get('emptyPlural', 1));
	Assert::same('0', $catalogue->get('emptyPlural', 3));
	Assert::null($catalogue->get('missing', 3));
	Assert::true($catalogue->has('empty'));
	Assert::true($catalogue->has('people'));
	Assert::false($catalogue->has('missing'));
	Assert::true($catalogue->has('options'));

	$plain = new Translator($catalogue);
	Assert::same('Hello', $plain->translate('hello'));
	Assert::same('missing', $plain->translate('missing'));
	Assert::same('', $plain->translate('empty'));
	Assert::same('0', $plain->translate('zero'));
	Assert::same('on', $plain->translate('options'));
	Assert::same('%d persons', $plain->translate('people'));
	Assert::same('3 people', $plain->translate('people', 3));

	$diagnostics = new Diagnostics();
	$debug = new DiagnosticCatalogue($catalogue, $diagnostics);
	Assert::same('on', $debug->get('options', 3));
	Assert::same('Plural form not defined. (message: options, form: few)', $diagnostics->getWarnings()->first());
	Assert::same('on', $debug->get('options'));
	Assert::same(2, $diagnostics->getWarnings()->count());

	$first = new Translator($catalogue, new Diagnostics());
	$second = new Translator($catalogue, $diagnostics);
	Assert::same('on', $first->translate('options', 8));
	Assert::same(2, $diagnostics->getWarnings()->count());
	Assert::same('3 people', $second->translate('people', ' 3 '));
	Assert::same('3 people', $second->translate('people', 3.9));
	Assert::same('%d persons', $second->translate('people'));
	Assert::same('on', $second->translate('options'));

	$warm = new CatalogueBuilder($storage, 'cs')->addSource('app', new StaticSource($messages, 'v1'))
		->setCheckProbability(0)->compile();
	Assert::same('%d people', $warm->get('people', 3));

	$english = new CatalogueBuilder($storage, 'en')->addSource('app', new StaticSource($messages, 'v1'))->compile();
	Assert::same('%d persons', $english->get('people', 3));
	$invariant = new CatalogueBuilder($storage, 'ja')->addSource('app', new StaticSource($messages, 'v1'))->compile();
	Assert::same('%d persons', $invariant->get('people', 1));
}

$path = TEMP_DIR . '/legacy';
mkdir($path);
$key = 'same-key';
$marker = $path . '/executed';
file_put_contents($path . '/' . hash('sha256', $key) . '.php', '<?php file_put_contents(' . var_export($marker, true) . ', "legacy"); return null;');
Assert::null(new PhpCatalogueStorage($path)->load($key));
Assert::false(is_file($marker));
