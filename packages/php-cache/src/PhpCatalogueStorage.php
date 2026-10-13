<?php

declare(strict_types=1);

namespace Bckp\Translator\PhpCache;

use Bckp\Translator\Catalogue;
use Bckp\Translator\CatalogueDefinition;
use Bckp\Translator\Interfaces\CatalogueStorage;
use Bckp\Translator\Plural;
use Bckp\Translator\PluralMessage;
use Bckp\Translator\PluralRule;
use Bckp\Translator\SourceVersion;
use RuntimeException;
use Throwable;

final readonly class PhpCatalogueStorage implements CatalogueStorage
{
	public function __construct(private string $path) {}

	#[\Override]
	public function load(string $key): ?Catalogue
	{
		$file = $this->filename($key);

		if (!is_file($file) || !is_readable($file)) {
			return null;
		}

		try {
			$catalogue = @include $file;

			return $catalogue instanceof Catalogue ? $catalogue : null;
		} catch (Throwable) {
			return null;
		}
	}

	#[\Override]
	public function save(string $key, CatalogueDefinition $definition): Catalogue
	{
		if (!is_dir($this->path) && !@mkdir($this->path, 0o775, true) && !is_dir($this->path)) {
			throw new RuntimeException("Cannot create catalogue directory '{$this->path}'.");
		}
		$temporary = @tempnam($this->path, '.catalogue-');

		if ($temporary === false) {
			throw new RuntimeException("Cannot write to catalogue directory '{$this->path}'.");
		}

		try {
			$code = $this->compile($definition);

			if (@file_put_contents($temporary, $code) !== strlen($code)) {
				throw new RuntimeException('Cannot write compiled catalogue.');
			}
			$catalogue = require $temporary;

			if (!$catalogue instanceof Catalogue) {
				throw new RuntimeException('The compiled file did not return a catalogue.');
			}
			$file = $this->filename($key);

			if (!@rename($temporary, $file)) {
				throw new RuntimeException('Cannot publish compiled catalogue.');
			}

			if (function_exists('opcache_invalidate')) {
				opcache_invalidate($file, true);
			}

			return $catalogue;
		} finally {
			if (function_exists('opcache_invalidate')) {
				opcache_invalidate($temporary, true);
			}

			if (is_file($temporary)) {
				@unlink($temporary);
			}
		}
	}

	private function filename(string $key): string
	{
		return $this->path . '/' . hash('sha256', '6:' . $key) . '.php';
	}

	private function compile(CatalogueDefinition $definition): string
	{
		$messages = '';
		$plurals = '';
		$definition->messages->each(static function (string $key, string|PluralMessage $value) use (&$messages, &$plurals): void {
			$exportedKey = var_export($key, true);

			if (is_string($value)) {
				$messages .= "\t\t\t\t" . $exportedKey . ' => ' . var_export($value, true) . ",\n";

				return;
			}
			$messages .= "\t\t\t\t" . $exportedKey . ' => $this->resolvePlural($key, $number),' . "\n";
			$plurals .= "\t\t\t" . $exportedKey . " => [\n";
			foreach (Plural::cases() as $plural) {
				$variant = $value->select($plural);

				if ($variant !== null) {
					$plurals .= "\t\t\t\t" . var_export($plural->value, true) . ' => ' . var_export($variant, true) . ",\n";
				}
			}
			$plurals .= "\t\t\t\t'fallback' => " . var_export($value->fallback, true) . ",\n\t\t\t],\n";
		});
		$versions = '';
		$definition->versions->each(static function (SourceVersion $version) use (&$versions): void {
			$versions .= "\t\t\t\t" . 'new \Bckp\Translator\SourceVersion(' . var_export($version->id, true)
				. ', ' . var_export($version->version, true) . "),\n";
		});
		$pluralBody = PluralMethodBody::extract($definition->pluralRule);
		$template = <<<'PHP'
			<?php

			declare(strict_types=1);

			namespace Bckp\Translator\Compiled;

			use Bckp\Translator\Plural;

			if (!class_exists(Generated__HASH__::class, false)) {
				final class Generated__HASH__ extends \Bckp\Translator\Catalogue
				{
					private const array Plurals = [
			__PLURALS__
					];

					public function __construct()
					{
						parent::__construct(__LOCALE__, __BUILD__, new \Bckp\Translator\SourceVersions(
			__VERSIONS__
						));
					}

					#[\Override]
					public function get(string $key, float|int|string|null $number = null): ?string
					{
						return match ($key) {
			__MESSAGES__
							default => null,
						};
					}

					#[\Override]
					public function missingPluralForm(string $key, float|int|string|null $number = null): ?\Bckp\Translator\Plural
					{
						$message = self::Plurals[$key] ?? null;

						if ($message === null) {
							return null;
						}
						$plural = is_numeric($number) ? $this->plural((int) $number) : \Bckp\Translator\Plural::Other;

						return isset($message[$plural->value]) ? null : $plural;
					}

					#[\Override]
					public function has(string $key): bool
					{
						return $this->get($key) !== null;
					}

					#[\Override]
					public function plural(int $number): \Bckp\Translator\Plural
					{
			__PLURAL_BODY__
					}

					private function resolvePlural(string $key, float|int|string|null $number): string
					{
						$message = self::Plurals[$key];
						return $message[
							(is_numeric($number) ? $this->plural((int) $number) : Plural::Other)->value
						] ?? $message['fallback'];
					}
				}
			}

			return new Generated__HASH__();
			PHP;
		$values = [
			'__MESSAGES__' => rtrim($messages, "\n"),
			'__PLURALS__' => rtrim($plurals, "\n"),
			'__LOCALE__' => var_export($definition->locale, true),
			'__BUILD__' => (string) $definition->build,
			'__VERSIONS__' => rtrim($versions, "\n"),
			'__PLURAL_BODY__' => "\t" . str_replace("\n", "\n\t", $pluralBody),
		];
		$values['__HASH__'] = hash('sha256', strtr($template, $values));

		return strtr($template, $values) . "\n";
	}
}
