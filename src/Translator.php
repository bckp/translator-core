<?php

declare(strict_types=1);

namespace Bckp\Translator;

use Bckp\Translator\Exceptions\TranslatorException;
use Bckp\Translator\Interfaces\Diagnostics;
use Closure;
use Stringable;
use UnexpectedValueException;
use ValueError;

final class Translator implements Interfaces\Translator
{
	private Closure $normalizeCallback;

	public function __construct(
		private Catalogue $catalogue,
		private readonly ?Diagnostics $diagnostics = null,
	) {
		$this->normalizeCallback = self::normalize(...);
		$this->diagnostics?->setLocale($catalogue->locale);
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
		$this->catalogue = $catalogue;
	}

	#[\Override]
	public function translate(string|Stringable $message, float|int|string ...$parameters): string
	{
		$message = (string) $message;

		if ($message === '') {
			return '';
		}

		$translation = $this->catalogue->get($message);

		if ($translation === null) {
			$this->diagnostics?->untranslated($message);

			return $message;
		}

		if ($translation instanceof PluralMessage) {
			$plural = is_numeric($parameters[0] ?? null)
				? $this->catalogue->plural((int) $parameters[0])
				: Plural::Other;
			$variant = $translation->select($plural);

			if ($variant === null) {
				$this->diagnostics?->warning("Plural form not defined. (message: $message, form: {$plural->value})");
			}
			$translation = $variant ?? $translation->fallback;
		}

		if ($parameters !== []) {
			$translation = ($this->normalizeCallback)($translation);

			if (!is_string($translation)) {
				throw new UnexpectedValueException('The normalize callback must return a string.');
			}

			try {
				$translation = vsprintf($translation, $parameters);
			} catch (ValueError $exception) {
				throw new TranslatorException("Invalid parameters for translation '$message'.", previous: $exception);
			}
		}

		return $translation;
	}
}
