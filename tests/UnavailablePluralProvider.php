<?php

declare(strict_types=1);

namespace Bckp\Translator;

use RuntimeException;

final class PluralProvider
{
	public static function csPlural(int $number): Plural
	{
		throw new RuntimeException('Compiled catalogues must contain their own Czech rule.');
	}

	public static function enPlural(int $number): Plural
	{
		throw new RuntimeException('Compiled catalogues must contain their own English rule.');
	}

	public static function zeroPlural(int $number): Plural
	{
		throw new RuntimeException('Compiled catalogues must contain their own invariant rule.');
	}
}
