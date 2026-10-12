<?php

declare(strict_types=1);

require __DIR__ . '/../vendor/autoload.php';

use Bckp\Translator\CatalogueBuilder;
use Bckp\Translator\MessageCatalogue;
use Bckp\Translator\PhpCache\PhpCatalogueStorage;
use Bckp\Translator\PluralMessage;
use Bckp\Translator\Sources\CallbackSource;
use Bckp\Translator\Translator;

$opcache = function_exists('opcache_get_status') ? opcache_get_status(false) : false;
echo 'PHP ', PHP_VERSION,
'; OPcache: ', ($opcache['opcache_enabled'] ?? false) ? 'enabled' : 'disabled',
'; JIT: ', ($opcache['jit']['on'] ?? false) ? 'enabled' : 'disabled', "\n";

$iterations = max(1, (int) ($argv[1] ?? 300000));
$loads = 0;
$versions = 0;
$source = new CallbackSource(
	static function (string $locale) use (&$loads): MessageCatalogue {
		++$loads;

		return new MessageCatalogue()->add('messages.hello', 'Hello')->add('messages.format', 'Hello %s')
			->add('messages.people', new PluralMessage(zero: 'nobody', one: '%d person', few: '%d people', other: '%d people'));
	},
	static function (string $locale) use (&$versions): string {
		++$versions;

		return 'benchmark-v1';
	},
);
$builder = new CatalogueBuilder(new PhpCatalogueStorage(sys_get_temp_dir() . '/translator-benchmark-v3'), 'cs')
	->setCheckProbability(0)->addSource('messages', $source);
$translator = new Translator($builder->compile());
$beforeLoads = $loads;
$beforeVersions = $versions;
foreach (['plain', 'format', 'plural'] as $case) {
	$samples = [];
	for ($round = 0; $round < 5; ++$round) {
		$start = hrtime(true);
		for ($i = 0; $i < $iterations; ++$i) {
			match ($case) {
				'plain' => $translator->translate('messages.hello'),
				'format' => $translator->translate('messages.format', 'Ada'),
				'plural' => $translator->translate('messages.people', 3),
			};
		}
		$samples[] = (hrtime(true) - $start) / $iterations;
	}
	sort($samples);
	echo $case, ': ', round($samples[2], 1), " ns/translation\n";
}

if ($loads !== $beforeLoads || $versions !== $beforeVersions) {
	throw new RuntimeException('Translation unexpectedly accessed a source.');
}
echo "Source calls during translation: 0; version checks during translation: 0\n";
