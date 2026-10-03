<?php

declare(strict_types=1);

namespace Bckp\Translator;

use InvalidArgumentException;

final class Locale
{
	public static function normalize(string $locale): string
	{
		$locale = strtolower(str_replace('_', '-', $locale));

		if (!preg_match('/^[a-z]{2,3}(?:-[a-z0-9]{2,8})*$/D', $locale)) {
			throw new InvalidArgumentException("Invalid locale: $locale");
		}

		return $locale;
	}
}
