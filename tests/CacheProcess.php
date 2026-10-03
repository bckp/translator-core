<?php

declare(strict_types=1);

require __DIR__ . '/../vendor/autoload.php';

use Bckp\Translator\CatalogueDefinition;
use Bckp\Translator\MessageCatalogue;
use Bckp\Translator\PhpCache\PhpCatalogueStorage;
use Bckp\Translator\PluralRule;
use Bckp\Translator\SourceVersion;
use Bckp\Translator\SourceVersions;

$storage = new PhpCatalogueStorage($argv[1]);
$value = $argv[2];

if ($value !== 'read') {
	$storage->save('shared', new CatalogueDefinition(
		'en',
		1,
		new SourceVersions(new SourceVersion('app', $value)),
		new MessageCatalogue()->add('hello', $value),
		PluralRule::English
	));
}
$catalogue = $storage->load('shared');

if ($catalogue === null || $catalogue->get('hello') !== $catalogue->versions->get('app')) {
	throw new RuntimeException('A reader observed an incomplete catalogue.');
}
echo $catalogue->get('hello');
