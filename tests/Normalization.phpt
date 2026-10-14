<?php

declare(strict_types=1);

require __DIR__ . '/bootstrap.php';

use Bckp\Translator\Catalogue;
use Bckp\Translator\CatalogueBuilder;
use Bckp\Translator\Diagnostics\Diagnostics;
use Bckp\Translator\Interfaces\CatalogueStorage;
use Bckp\Translator\Interfaces\FormattedCatalogue;
use Bckp\Translator\MessageCatalogue;
use Bckp\Translator\PhpCache\PhpCatalogueStorage;
use Bckp\Translator\Plural;
use Bckp\Translator\PluralMessage;
use Bckp\Translator\Sources\StaticSource;
use Bckp\Translator\Storage\MemoryStorage;
use Bckp\Translator\Translator;
use Tester\Assert;

$messages = new MessageCatalogue()
	->add('plain', 'Hello')
	->add('formatted', 'Hello %s')
	->add('tokens', '%label %value %name: %s')
	->add('empty', '')
	->add('zero', '0')
	->add('plural', new PluralMessage(one: '%label: %d person', few: '%value: %d people', other: '%name: %d people'))
	->add('fallback', new PluralMessage(one: '%label: %d option'));

foreach ([new MemoryStorage(), new PhpCatalogueStorage(TEMP_DIR . '/normalization')] as $storage) {
	$builder = new CatalogueBuilder($storage, 'cs')->addSource('app', new StaticSource($messages, 'v1'));
	$catalogue = $builder->compile();
	Assert::type(FormattedCatalogue::class, $catalogue);
	Assert::same('%label %value %name: %s', $catalogue->get('tokens'));
	Assert::same('%%label %%value %%name: %s', $catalogue->format('tokens'));
	Assert::same('Hello %s', $catalogue->format('formatted'));
	Assert::same('', $catalogue->format('empty'));
	Assert::same('0', $catalogue->format('zero'));
	Assert::null($catalogue->format('missing'));

	$warm = new CatalogueBuilder($storage, 'cs')->addSource('app', new StaticSource($messages, 'v1'))
		->setCheckProbability(0)->compile();
	Assert::type(FormattedCatalogue::class, $warm);
	Assert::same('%%value: %d people', $warm->format('plural', 3));
	Assert::same('%%label: %d option', $warm->format('fallback', 3));

	foreach ([null, new Diagnostics()] as $diagnostics) {
		$translator = new Translator($warm, $diagnostics);
		$other = new Translator($warm);
		Assert::same('%label %value %name: Ada', $translator->translate('tokens', 'Ada'));
		Assert::same('%label %value %name: Ada', $translator->translate('tokens', name: 'Ada'));
		Assert::same('%label %value %name: %s', $translator->translate('tokens'));
		Assert::same('%label: 1 person', $translator->translate('plural', 1));
		Assert::same('%value: 3 people', $translator->translate('plural', '3'));
		Assert::same('%value: 3 people', $translator->translate('plural', 3.9));
		Assert::same('%name: 8 people', $translator->translate('plural', 8));
		Assert::same('%label: 3 option', $translator->translate('fallback', 3));
		Assert::same('missing', $translator->translate('missing', 3));
		Assert::same('', $translator->translate('empty', 'Ada'));
		Assert::same('0', $translator->translate('zero', 'Ada'));

		if ($diagnostics !== null) {
			Assert::same(1, $diagnostics->getWarnings()->count());
			Assert::same(1, $diagnostics->getUntranslated()->count());
		}
		$calls = 0;
		$callback = static function (string $text) use (&$calls): string {
			++$calls;

			return $calls . ': ' . Translator::normalize($text);
		};
		Assert::error(static fn() => $translator->setNormalizeCallback($callback), E_USER_DEPRECATED);
		Assert::same('%label %value %name: %s', $translator->translate('tokens'));
		Assert::same('missing', $translator->translate('missing', 'Ada'));
		Assert::same(0, $calls);
		Assert::same('1: %label %value %name: Ada', $translator->translate('tokens', 'Ada'));
		Assert::same('2: %label %value %name: Bea', $translator->translate('tokens', 'Bea'));
		Assert::same('%label %value %name: Ada', $other->translate('tokens', 'Ada'));

		$replacement = new CatalogueBuilder($storage, 'cs', namespace: 'replacement')
			->addSource('app', new StaticSource(new MessageCatalogue()->add('tokens', '%value: %s'), 'v2'))->compile();
		$translator->replaceCatalogue($replacement);
		Assert::same('3: %value: Ada', $translator->translate('tokens', 'Ada'));
		Assert::error(static fn() => $translator->setNormalizeCallback(Translator::normalize(...)), E_USER_DEPRECATED);
		Assert::same('%value: Ada', $translator->translate('tokens', 'Ada'));
		Assert::error(static fn() => $translator->setNormalizeCallback(static fn(string $text): int => 1), E_USER_DEPRECATED);
		Assert::exception(static fn() => $translator->translate('tokens', 'Ada'), UnexpectedValueException::class);
	}

	$legacy = new class ($warm) extends Catalogue {
		public function __construct(private readonly Catalogue $catalogue)
		{
			parent::__construct($catalogue->locale, $catalogue->build, $catalogue->versions);
		}

		#[\Override]
		public function get(string $key, float|int|string|null $number = null): ?string
		{
			return $this->catalogue->get($key, $number);
		}

		#[\Override]
		public function missingPluralForm(string $key, float|int|string|null $number = null): ?Plural
		{
			return $this->catalogue->missingPluralForm($key, $number);
		}

		#[\Override]
		public function has(string $key): bool
		{
			return $this->catalogue->has($key);
		}

		#[\Override]
		public function plural(int $number): Plural
		{
			return $this->catalogue->plural($number);
		}
	};
	foreach ([null, new Diagnostics()] as $diagnostics) {
		$translator = new Translator($legacy, $diagnostics);
		Assert::same('%label %value %name: Ada', $translator->translate('tokens', 'Ada'));
		Assert::same('%value: 3 people', $translator->translate('plural', 3));
		Assert::same('missing', $translator->translate('missing', 3));
		Assert::error(
			static fn() => $translator->setNormalizeCallback(static fn(string $text): string => 'Custom ' . Translator::normalize($text)),
			E_USER_DEPRECATED,
		);
		Assert::same('Custom %label: 3 option', $translator->translate('fallback', 3));
	}

	foreach ([false, true] as $cached) {
		$legacyStorage = new class ($legacy, $cached) implements CatalogueStorage {
			public function __construct(private readonly Catalogue $catalogue, private readonly bool $cached) {}

			#[\Override]
			public function load(string $key): ?Catalogue
			{
				return $this->cached ? $this->catalogue : null;
			}

			#[\Override]
			public function save(string $key, Bckp\Translator\CatalogueDefinition $definition): Catalogue
			{
				return $this->catalogue;
			}
		};
		Assert::exception(
			static fn() => new CatalogueBuilder($legacyStorage, 'cs')->setCheckProbability(0)
				->addSource('app', new StaticSource($messages, 'v1'))
				->setNormalizer(Translator::normalize(...), 'custom')->compile(),
			LogicException::class,
			'Storage must return a FormattedCatalogue to support compile-time normalization.',
		);
	}
}

$path = TEMP_DIR . '/previous-format';
mkdir($path);
$marker = $path . '/executed';
file_put_contents($path . '/' . hash('sha256', '6:rule') . '.php', '<?php file_put_contents(' . var_export($marker, true) . ', "old"); return null;');
Assert::null(new PhpCatalogueStorage($path)->load('rule'));
Assert::false(is_file($marker));
