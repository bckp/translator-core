<?php

declare(strict_types=1);

namespace Bckp\Translator;

use Bckp\Translator\Exceptions\TranslatorException;
use Bckp\Translator\Diagnostics\DiagnosticCatalogue;
use Bckp\Translator\Interfaces\Diagnostics;
use Closure;
use Stringable;
use UnexpectedValueException;
use ValueError;

use function is_string;
use function str_replace;
use function vsprintf;

final class Translator implements Interfaces\Translator
{
	private Closure $normalizeCallback;

	public function __construct(
		private Catalogue $catalogue,
		private readonly ?Diagnostics $diagnostics = null,
	) {
		$this->normalizeCallback = self::normalize(...);

		if ($diagnostics !== null) {
			$this->catalogue = new DiagnosticCatalogue($catalogue, $diagnostics);
		}
	}

	public static function normalize(string $string): string
	{
		return str_replace(
			['%label', '%value', '%name'],
			['%%label', '%%value', '%%name'],
			$string,
		);
	}

	#[\Override]
	public function setNormalizeCallback(Closure $callback): void
	{
		$this->normalizeCallback = $callback;
	}

	#[\Override]
	public function getLocale(): string
	{
		return $this->catalogue->locale;
	}

	#[\Override]
	public function replaceCatalogue(Catalogue $catalogue): void
	{
		if ($catalogue->locale !== $this->catalogue->locale) {
			throw new TranslatorException('A replacement catalogue must use the same locale.');
		}
		$this->catalogue = $this->diagnostics === null ? $catalogue : new DiagnosticCatalogue($catalogue, $this->diagnostics);
	}

	#[\Override]
	public function translate(string|Stringable $message, float|int|string ...$parameters): string
	{
		$message = (string) $message;

		if ($message === '') {
			return '';
		}

		if ($parameters === []) {
			return $this->catalogue->get($message) ?? $message;
		}
		$translation = $this->catalogue->get($message, $parameters[0] ?? null);

		if ($translation === null) {
			return $message;
		}

		$translation = ($this->normalizeCallback)($translation);

		if (!is_string($translation)) {
			throw new UnexpectedValueException('The normalize callback must return a string.');
		}

		try {
			return vsprintf($translation, $parameters);
		} catch (ValueError $exception) {
			throw new TranslatorException("Invalid parameters for translation '$message'.", previous: $exception);
		}
	}
}
