<?php

declare(strict_types=1);

namespace Bckp\Translator;

enum PluralRule: string
{
	case Czech = 'cs';
	case English = 'en';
	case Invariant = 'invariant';

	public function select(int $number): Plural
	{
		return match ($this) {
			self::Czech => PluralProvider::csPlural($number),
			self::English => PluralProvider::enPlural($number),
			self::Invariant => PluralProvider::zeroPlural($number),
		};
	}
}
