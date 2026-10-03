<?php

declare(strict_types=1);

namespace Bckp\Translator;

class PluralProvider
{
	public static function csPlural(int $number): Plural
	{
		return match (true) {
			$number === 0 => Plural::Zero,
			$number === 1 => Plural::One,
			$number >= 2 && $number <= 4 => Plural::Few,
			default => Plural::Other,
		};
	}

	public static function enPlural(int $number): Plural
	{
		return match ($number) {
			0 => Plural::Zero,
			1 => Plural::One,
			default => Plural::Other,
		};
	}

	public static function zeroPlural(int $number): Plural
	{
		return $number === 0 ? Plural::Zero : Plural::Other;
	}

	public function getRule(string $locale): PluralRule
	{
		$language = explode('-', Locale::normalize($locale))[0];

		return match ($language) {
			'cs' => PluralRule::Czech,
			'id', 'ja', 'ka', 'ko', 'lo', 'ms', 'my', 'th', 'vi', 'zh' => PluralRule::Invariant,
			default => PluralRule::English,
		};
	}
}
