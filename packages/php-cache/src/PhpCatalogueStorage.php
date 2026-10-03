<?php

declare(strict_types=1);

namespace Bckp\Translator\PhpCache;

use Bckp\Translator\Catalogue;
use Bckp\Translator\CatalogueDefinition;
use Bckp\Translator\Interfaces\CatalogueStorage;
use Bckp\Translator\PluralMessage;
use Bckp\Translator\PluralRule;
use Bckp\Translator\SourceVersion;
use RuntimeException;
use Throwable;

final readonly class PhpCatalogueStorage implements CatalogueStorage
{
	public function __construct(private string $path)
	{
	}

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
		return $this->path . '/' . hash('sha256', $key) . '.php';
	}

	private function compile(CatalogueDefinition $definition): string
	{
		$messages = '';
		$initialization = '';
		$definition->messages->each(function (string $key, string|PluralMessage $value) use (&$messages, &$initialization): void {
			$exportedKey = var_export($key, true);
			$messages .= $exportedKey . ' => ' . (is_string($value) ? var_export($value, true) : 'null') . ",\n";

			if ($value instanceof PluralMessage) {
				$initialization .= 'self::$messages[' . $exportedKey . '] = ' . $this->pluralCode($value) . ";\n";
			}
		});
		$versions = '';
		$definition->versions->each(static function (SourceVersion $version) use (&$versions): void {
			$versions .= 'new \Bckp\Translator\SourceVersion(' . var_export($version->id, true)
				. ', ' . var_export($version->version, true) . "),\n";
		});
		$pluralMethod = match ($definition->pluralRule) {
			PluralRule::Czech => 'csPlural',
			PluralRule::English => 'enPlural',
			PluralRule::Invariant => 'zeroPlural',
		};
		$template = <<<'PHP'
			<?php

			declare(strict_types=1);

			namespace Bckp\Translator\Compiled;

			if (!class_exists(Generated__HASH__::class, false)) {
				final class Generated__HASH__ extends \Bckp\Translator\Catalogue
				{
					private static array $messages = [
			__MESSAGES__
					];
					private static bool $initialized = false;

					public function __construct()
					{
						parent::__construct(__LOCALE__, __BUILD__, new \Bckp\Translator\SourceVersions(
			__VERSIONS__
						));
						if (!self::$initialized) {
			__INITIALIZATION__
							self::$initialized = true;
						}
					}

					#[\Override]
					public function get(string $key): string|\Bckp\Translator\PluralMessage|null
					{
						return self::$messages[$key] ?? null;
					}

					#[\Override]
					public function has(string $key): bool
					{
						return array_key_exists($key, self::$messages);
					}

					#[\Override]
					public function plural(int $number): \Bckp\Translator\Plural
					{
						return \Bckp\Translator\PluralProvider::__PLURAL_METHOD__($number);
					}
				}
			}

			return new Generated__HASH__();
			PHP;
		$values = [
			'__MESSAGES__' => $messages,
			'__INITIALIZATION__' => $initialization,
			'__LOCALE__' => var_export($definition->locale, true),
			'__BUILD__' => (string) $definition->build,
			'__VERSIONS__' => $versions,
			'__PLURAL_METHOD__' => $pluralMethod,
		];
		$values['__HASH__'] = hash('sha256', strtr($template, $values));

		return strtr($template, $values);
	}

	private function pluralCode(PluralMessage $message): string
	{
		return 'new \Bckp\Translator\PluralMessage('
			. 'zero: ' . var_export($message->zero, true) . ', '
			. 'one: ' . var_export($message->one, true) . ', '
			. 'two: ' . var_export($message->two, true) . ', '
			. 'few: ' . var_export($message->few, true) . ', '
			. 'many: ' . var_export($message->many, true) . ', '
			. 'other: ' . var_export($message->other, true) . ')';
	}
}
