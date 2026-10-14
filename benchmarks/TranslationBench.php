<?php

declare(strict_types=1);

namespace Bckp\Translator\Benchmarks;

use Bckp\Translator\CatalogueBuilder;
use Bckp\Translator\Catalogue;
use Bckp\Translator\MessageCatalogue;
use Bckp\Translator\PhpCache\PhpCatalogueStorage;
use Bckp\Translator\PluralMessage;
use Bckp\Translator\Sources\CallbackSource;
use Bckp\Translator\Sources\StaticSource;
use Bckp\Translator\Translator;
use PhpBench\Attributes as Bench;
use ReflectionClass;
use RuntimeException;

#[Bench\BeforeClassMethods(['warmCatalogue'])]
#[Bench\BeforeMethods(['setUp'])]
#[Bench\AfterMethods(['checkSourceCalls'])]
#[Bench\Revs(100000)]
#[Bench\Iterations(10)]
#[Bench\Warmup(10000)]
#[Bench\OutputTimeUnit('microseconds')]
final class TranslationBench
{
	private Translator $translator;

	private Catalogue $catalogue;

	private int $loads = 0;

	private int $versions = 0;

	public static function warmCatalogue(): void
	{
		self::builder()->addSource('messages', new StaticSource(self::messages(), 'phpbench-v1'))->forceRecompile();
	}

	public function setUp(): void
	{
		$source = new CallbackSource(
			function (string $locale): MessageCatalogue {
				++$this->loads;

				return self::messages();
			},
			function (string $locale): string {
				++$this->versions;

				return 'phpbench-v1';
			},
		);
		$catalogue = self::builder()->addSource('messages', $source)->compile();
		$file = new ReflectionClass($catalogue)->getFileName();

		if ($file === false || !opcache_is_script_cached($file)) {
			throw new RuntimeException('The compiled catalogue must be cached by OPcache before measurement.');
		}
		$this->translator = new Translator($catalogue);
		$this->catalogue = $catalogue;
		$this->loads = 0;
		$this->versions = 0;
	}

	public function benchPlain(): void
	{
		$this->translator->translate('messages.hello');
	}

	public function benchFormatted(): void
	{
		$this->translator->translate('messages.format', 'Ada');
	}

	public function benchFormattedNumber(): void
	{
		$this->translator->translate('messages.count', 3);
	}

	public function benchNormalized(): void
	{
		$this->translator->translate('messages.field', 'Ada');
	}

	public function benchMissing(): void
	{
		$this->translator->translate('messages.missing');
	}

	public function benchPlural(): void
	{
		$this->translator->translate('messages.people', 3);
	}

	public function benchPluralRule(): void
	{
		$this->catalogue->plural(3);
	}

	public function benchPluralLookup(): void
	{
		$this->catalogue->get('messages.people', 3);
	}

	public function checkSourceCalls(): void
	{
		if ($this->loads !== 0 || $this->versions !== 0) {
			throw new RuntimeException('Translation accessed a source or checked its version.');
		}
	}

	private static function builder(): CatalogueBuilder
	{
		return new CatalogueBuilder(
			new PhpCatalogueStorage(__DIR__ . '/../temp/phpbench'),
			'cs',
			namespace: 'phpbench',
		)->setCheckProbability(0);
	}

	private static function messages(): MessageCatalogue
	{
		return new MessageCatalogue()
			->add('messages.hello', 'Hello')
			->add('messages.format', 'Hello %s')
			->add('messages.count', '%d items')
			->add('messages.field', '%label: %s')
			->add('messages.people', new PluralMessage(
				zero: 'nobody',
				one: '%d person',
				few: '%d people',
				other: '%d people',
			));
	}
}
