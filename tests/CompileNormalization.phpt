<?php

declare(strict_types=1);

require __DIR__ . '/bootstrap.php';

use Bckp\Translator\CatalogueBuilder;
use Bckp\Translator\CatalogueDefinition;
use Bckp\Translator\MessageCatalogue;
use Bckp\Translator\PhpCache\PhpCatalogueStorage;
use Bckp\Translator\PluralMessage;
use Bckp\Translator\PluralRule;
use Bckp\Translator\Sources\CallbackSource;
use Bckp\Translator\SourceVersions;
use Bckp\Translator\Storage\MemoryStorage;
use Bckp\Translator\Translator;
use Tester\Assert;

$messages = new MessageCatalogue()
	->add('plain', 'Hello')
	->add('formatted', 'Hello %s')
	->add('product', '%product: %s')
	->add('plural', new PluralMessage(zero: '%product: no items', one: '%product: %d item', other: '%product: %d items'));

foreach ([new MemoryStorage(), new PhpCatalogueStorage(TEMP_DIR . '/compile-normalization')] as $storage) {
	$loads = 0;
	$versions = 0;
	$source = new CallbackSource(
		static function (string $locale) use ($messages, &$loads): MessageCatalogue {
			++$loads;

			return $messages;
		},
		static function (string $locale) use (&$versions): string {
			++$versions;

			return 'v1';
		},
	);
	$request = static fn(): CatalogueBuilder => new CatalogueBuilder($storage, 'cs')
		->setCheckProbability(0)->addSource('app', $source);
	Assert::same('Hello Ada', new Translator($request()->compile())->translate('formatted', 'Ada'));
	$normalizations = 0;
	$prefix = 'A ';
	$normalizer = static function (string $text) use (&$normalizations, &$prefix): string {
		++$normalizations;

		return $prefix . Translator::normalize(str_replace('%product', '%%product', $text));
	};
	$builder = $request()->setNormalizer($normalizer, 'default');
	$catalogue = $builder->compile();
	Assert::same(6, $normalizations);
	Assert::same(2, $loads);
	$translator = new Translator($catalogue);
	Assert::same('Hello', $translator->translate('plain'));
	Assert::same('%product: %s', $catalogue->get('product'));
	Assert::same('A %product: Ada', $translator->translate('product', 'Ada'));
	Assert::same('A %product: Bea', $translator->translate('product', name: 'Bea'));
	Assert::same('A %product: 1 item', $translator->translate('plural', 1));
	Assert::same('A %product: 3 items', $translator->translate('plural', 3));
	Assert::same('%product: %d items', $translator->translate('plural'));
	Assert::same('missing', $translator->translate('missing', 'Ada'));
	Assert::same(6, $normalizations);
	Assert::same(2, $loads);
	Assert::same(2, $versions);
	$unexpected = static fn(string $text): never => throw new RuntimeException('A warm catalogue must not run the normalizer.');
	$warm = $request()->setNormalizer($unexpected, 'default')->compile();
	Assert::same('A %product: Ada', new Translator($warm)->translate('product', 'Ada'));
	Assert::same(2, $loads);
	Assert::same(2, $versions);
	Assert::exception(static fn() => $builder->setNormalizer($normalizer, 'later'), LogicException::class);
	Assert::exception(static fn() => $request()->setNormalizer($normalizer, ''), InvalidArgumentException::class);
	$other = $request()->setNormalizer(static fn(string $text): string => 'B ' . $text, 'b-v1')->compile();
	Assert::same('B Hello Ada', new Translator($other)->translate('formatted', 'Ada'));
	Assert::same(3, $loads);
	Assert::same('Hello Ada', new Translator($request()->compile())->translate('formatted', 'Ada'));
	Assert::same(3, $loads);
	$prefix = 'Updated ';
	$translator->replaceCatalogue($builder->forceRecompile());
	Assert::same('Updated %product: Ada', $translator->translate('product', 'Ada'));
	Assert::same(12, $normalizations);
	Assert::same(4, $loads);
	Assert::exception(static fn() => $request()->setNormalizer($unexpected, 'default')->forceRecompile(), RuntimeException::class);
	Assert::same('Updated %product: Ada', new Translator($request()->setNormalizer($unexpected, 'default')->compile())->translate('product', 'Ada'));
	Assert::exception(
		static fn() => $request()->setNormalizer(static fn(string $text): int => 1, 'invalid')->compile(),
		UnexpectedValueException::class,
	);
}

$definition = new CatalogueDefinition('cs', 123, new SourceVersions(), new MessageCatalogue()->add('field', '%label: %s'), PluralRule::Czech);
$restored = unserialize(serialize($definition));
Assert::type(CatalogueDefinition::class, $restored);
Assert::same('%%label: %s', $restored->normalizedMessages()->get('field'));

foreach ([Translator::class, Bckp\Translator\Interfaces\Translator::class] as $type) {
	$attributes = new ReflectionMethod($type, 'setNormalizeCallback')->getAttributes(Deprecated::class);
	Assert::same(1, count($attributes));
	Assert::contains('CatalogueBuilder::setNormalizer()', $attributes[0]->newInstance()->message);
}
