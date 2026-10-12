<?php

declare(strict_types=1);

namespace Bckp\Translator\Diagnostics;

use Bckp\Translator\Catalogue;
use Bckp\Translator\Interfaces\Diagnostics;
use Bckp\Translator\Plural;

final class DiagnosticCatalogue extends Catalogue
{
	public function __construct(
		private readonly Catalogue $catalogue,
		private readonly Diagnostics $diagnostics,
	) {
		parent::__construct($catalogue->locale, $catalogue->build, $catalogue->versions);
		$this->diagnostics->setLocale($catalogue->locale);
	}

	#[\Override]
	public function get(string $key, float|int|string|null $number = null): ?string
	{
		$translation = $this->catalogue->get($key, $number);

		if ($translation === null) {
			$this->diagnostics->untranslated($key);
		} else {
			$plural = $this->catalogue->missingPluralForm($key, $number);

			if ($plural !== null) {
				$this->diagnostics->warning("Plural form not defined. (message: $key, form: {$plural->value})");
			}
		}

		return $translation;
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
}
