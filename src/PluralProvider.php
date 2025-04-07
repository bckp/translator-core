<?php

/**
 * BCKP Translator
 * (c) Radovan Kepák
 *
 * For the full copyright and license information, please view
 * the file license.md that was distributed with this source code.
 *
 * @author Radovan Kepak <radovan@kepak.eu>
 */

declare(strict_types=1);

namespace Bckp\Translator;

use Closure;

use function strtolower;

final class PluralProvider
{
	/**
	 * Czech plural selector (zero-one-few-other)
	 */
	public static function csPlural(?int $n): Plural
	{
		return match (true) {
			$n === 0 => Plural::Zero,
			$n === 1 => Plural::One,
			$n >= 2 && $n <= 4 => Plural::Few,
			default => Plural::Other,
		};
	}

	/**
	 * Default plural detector (zero-one-other)
	 */
	public static function enPlural(?int $n): Plural
	{
		return match (true) {
			$n === 0 => Plural::Zero,
			$n === 1 => Plural::One,
			default => Plural::Other,
		};
	}

	/**
	 * No plural detector (zero-other)
	 */
	public static function zeroPlural(?int $n): Plural
	{
		return $n === 0
			? Plural::Zero
			: Plural::Other;
	}

	public function getPlural(string $locale): callable
	{
		return match (strtolower($locale)) {
			'cs' => [$this, 'csPlural'],
			'id', 'ja', 'ka', 'ko', 'lo', 'ms', 'my', 'th', 'vi', 'zh' => [$this, 'zeroPlural'],
			default => [$this, 'enPlural'],
		};
	}
}
