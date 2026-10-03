<?php

declare(strict_types=1);

namespace Bckp\Translator\Neon;

use Bckp\Translator\Interfaces\TranslationSource;
use Bckp\Translator\Locale;
use Bckp\Translator\MessageCatalogue;
use Bckp\Translator\Plural;
use Bckp\Translator\PluralMessage;
use Bckp\Translator\StringList;
use InvalidArgumentException;
use Nette\Neon\Neon;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use RuntimeException;
use SplFileInfo;
use Throwable;

final readonly class NeonSource implements TranslationSource
{
	private StringList $paths;

	public function __construct(string|StringList $paths, private ?string $version = null)
	{
		$this->paths = is_string($paths) ? new StringList($paths) : clone $paths;

		if ($this->paths->isEmpty()) {
			throw new InvalidArgumentException('A NEON source needs at least one directory.');
		}
	}

	#[\Override]
	public function getVersion(string $locale): string
	{
		if ($this->version !== null) {
			return $this->version;
		}
		$context = hash_init('sha256');
		$this->resources($locale)->each(static function (NeonResource $resource) use ($context): void {
			$hash = @hash_file('sha256', $resource->path);

			if ($hash === false) {
				throw new RuntimeException("Cannot read translation file '{$resource->path}'.");
			}
			hash_update($context, strlen($resource->path) . ':' . $resource->path . $hash);
		});

		return hash_final($context);
	}

	#[\Override]
	public function __invoke(string $locale): MessageCatalogue
	{
		$catalogue = new MessageCatalogue();
		$this->resources($locale)->each(function (NeonResource $resource) use ($catalogue): void {
			$content = @file_get_contents($resource->path);

			if ($content === false) {
				throw new RuntimeException("Cannot read translation file '{$resource->path}'.");
			}

			if (trim($content) === '') {
				return;
			}

			try {
				$data = Neon::decode($content);
			} catch (Throwable $exception) {
				throw new InvalidTranslationException("Invalid NEON in '{$resource->path}'.", previous: $exception);
			}

			if (!is_array($data)) {
				throw new InvalidTranslationException("Translations in '{$resource->path}' must be a map.");
			}
			foreach ($data as $key => $value) {
				$catalogue->add($resource->prefix . '.' . $key, $this->message($value, $resource->path, (string) $key));
			}
		});

		return $catalogue;
	}

	private function resources(string $locale): NeonResources
	{
		$locale = Locale::normalize($locale);
		$resources = [];
		$this->paths->each(static function (string $path) use ($locale, &$resources): void {
			if (!is_dir($path) || !is_readable($path)) {
				throw new RuntimeException("Cannot read translation directory '$path'.");
			}
			$files = [];
			$iterator = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($path, RecursiveDirectoryIterator::SKIP_DOTS));
			foreach ($iterator as $file) {
				if (!$file instanceof SplFileInfo || !$file->isFile()) {
					continue;
				}

				if (preg_match('/^(.+)\.([a-z]{2,3}(?:[-_][a-z0-9]{2,8})*)\.neon$/iD', $file->getFilename(), $matches) !== 1) {
					continue;
				}

				if (Locale::normalize($matches[2]) !== $locale) {
					continue;
				}
				$files[$file->getPathname()] = new NeonResource($file->getPathname(), strtolower($matches[1]));
			}
			ksort($files, SORT_STRING);
			foreach ($files as $file) {
				$resources[] = $file;
			}
		});

		return new NeonResources(...$resources);
	}

	private function message(mixed $value, string $file, string $key): string|PluralMessage
	{
		if (is_string($value)) {
			return $value;
		}

		if (!is_array($value) || $value === []) {
			throw new InvalidTranslationException("Translation '$key' in '$file' must be a string or plural map.");
		}
		foreach ($value as $form => $text) {
			if (!is_string($form) || Plural::tryFrom($form) === null || !is_string($text)) {
				throw new InvalidTranslationException("Invalid plural variant in '$file' for '$key'.");
			}
		}

		return new PluralMessage(
			zero: $this->variant($value['zero'] ?? null),
			one: $this->variant($value['one'] ?? null),
			two: $this->variant($value['two'] ?? null),
			few: $this->variant($value['few'] ?? null),
			many: $this->variant($value['many'] ?? null),
			other: $this->variant($value['other'] ?? null),
		);
	}

	private function variant(mixed $value): ?string
	{
		if ($value !== null && !is_string($value)) {
			throw new InvalidTranslationException('Plural variants must be strings.');
		}

		return $value;
	}
}
