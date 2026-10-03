<?php

declare(strict_types=1);

require __DIR__ . '/bootstrap.php';
require __DIR__ . '/CountingSource.php';

use Bckp\Translator\CatalogueBuilder;
use Bckp\Translator\CatalogueDefinition;
use Bckp\Translator\MessageCatalogue;
use Bckp\Translator\PhpCache\PhpCatalogueStorage;
use Bckp\Translator\Plural;
use Bckp\Translator\PluralMessage;
use Bckp\Translator\PluralRule;
use Bckp\Translator\SourceVersion;
use Bckp\Translator\SourceVersions;
use Bckp\Translator\Tests\CountingSource;
use Tester\Assert;

$storage = new PhpCatalogueStorage(TEMP_DIR . '/cache');
$source = new CountingSource('v1', 'Old');
$request = static fn(): CatalogueBuilder => (new CatalogueBuilder($storage, 'cs'))->addSource('app', $source)->setCheckProbability(1);
$active = $request();
Assert::same('Old', $active->compile()->get('messages.hello'));
$source->text = 'New with unchanged version';
Assert::same('New with unchanged version', $active->forceRecompile()->get('messages.hello'));
Assert::same('New with unchanged version', $request()->compile()->get('messages.hello'));
$source->fail = true;
Assert::exception(static fn() => $active->forceRecompile(), RuntimeException::class);
Assert::same('New with unchanged version', $request()->setCheckProbability(0)->compile()->get('messages.hello'));
Assert::same([], glob(TEMP_DIR . '/cache/.catalogue-*'));
$source->fail = false;
$files = glob(TEMP_DIR . '/cache/*.php');
file_put_contents($files[0], '<?php broken');
Assert::same('New with unchanged version', $request()->compile()->get('messages.hello'));
$literal = "__HASH__ __MESSAGES__ __LOCALE__ ' \\\\ \n";
$definition = new CatalogueDefinition(
	'cs',
	123,
	new SourceVersions(new SourceVersion('app', $literal)),
	(new MessageCatalogue())->add('literal', $literal)->add('0', '0')->add('empty', '')
		->add('people', new PluralMessage(one: '%d person', few: '%d people', other: '%d people')),
	PluralRule::Czech
);
$stored = $storage->save('../../arbitrary-key', $definition);
$loaded = $storage->load('../../arbitrary-key');
Assert::same($literal, $stored->get('literal'));
Assert::same($literal, $loaded->get('literal'));
Assert::same($literal, $loaded->versions->get('app'));
Assert::same('0', $loaded->get('0'));
Assert::same('', $loaded->get('empty'));
Assert::true($loaded->has('empty'));
Assert::false($loaded->has('missing'));
Assert::same(Plural::Few, $loaded->plural(3));
Assert::type(PluralMessage::class, $loaded->get('people'));
