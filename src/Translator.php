<?php

declare(strict_types=1);

/**
 * BCKP Translator
 * (c) Radovan Kepák
 *
 * For the full copyright and license information, please view
 * the file license.md that was distributed with this source code.
 *
 * @author Radovan Kepak <radovan@kepak.dev>
 */

namespace Bckp\Translator;

use Bckp\Translator\Interfaces\Diagnostics;
use Stringable;

use function array_key_exists;
use function array_key_last;
use function is_array;
use function vsprintf;

final class Translator implements Interfaces\Translator
{
	/** @var callable function(string $string): string */
	protected $normalizeCallback;

	public function __construct(
		private readonly Catalogue $catalogue,
		private readonly ?Diagnostics $diagnostics = null
	) {
		$this->normalizeCallback = [$this, 'normalize'];
		$this->diagnostics?->setLocale($catalogue->locale);
	}

	/**
	 * @api
	 */
	public function normalize(string $string): string
	{
		return str_replace(
			['%label', '%value', '%name'],
			['%%label', '%%value', '%%name'],
			$string
		);
	}

	/**
	 * @api
	 */
	#[\Override]
	public function setNormalizeCallback(callable $callback): void
	{
		$this->normalizeCallback = $callback;
	}

	/**
	 * @api
	 */
	#[\Override]
	public function getLocale(): string
	{
		return $this->catalogue->locale;
	}

	/**
	 * @api
	 */
	#[\Override]
	public function translate(string|Stringable $message, float|int|string ...$parameters): string
	{
		$message = (string) $message;

		if (empty($message)) {
			return '';
		}

		$translation = $this->catalogue->get($message);
		if (!$translation) {
			$this->untranslated($message);
			return $message;
		}

		// Plural option is returned, we need to choose the right one
		if (is_array($translation)) {
			$plural = is_numeric($parameters[0] ?? null) ? $this->catalogue->plural((int) $parameters[0]) : Plural::Other;
			$translation = $this->getVariant($message, $translation, $plural);
		}

		if (!empty($parameters)) {
			$translation = ($this->normalizeCallback)($translation);
			$translation = @vsprintf($translation, $parameters);
		}

		return $translation;
	}

	/**
	 * @api
	 * @param array<string, string> $translations
	 */
	public function getVariant(string $message, array $translations, Plural $plural): string
	{
		if (!array_key_exists($plural->value, $translations)) {
			$this->warn(
				'Plural form not defined. (message: %s, form: %s)',
				$message,
				$plural->value,
			);
		}

		return $translations[$plural->value] ?? $translations[array_key_last($translations)] ?? $message;
	}

	protected function untranslated(string $message): void
	{
		$this->diagnostics?->untranslated($message);
	}

	protected function warn(string $message, float|int|string ...$parameters): void
	{
		if (!empty($parameters)) {
			$message = @vsprintf($message, $parameters);
		} // Intentionally @ as parameter count can mismatch

		$this->diagnostics?->warning($message);
	}
}
