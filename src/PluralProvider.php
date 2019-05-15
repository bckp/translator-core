<?php declare(strict_types=1);
/**
 * BCKP Translator
 * (c) Radovan Kepák
 *
 * For the full copyright and license information, please view the file license.md that was distributed with this source code.
 *
 * @author Radovan Kepak <radovan@kepak.eu>
 *  --------------------------------------------------------------------------
 */

namespace Bckp\Translator;

/**
 * Class PluralProvider
 *
 * @package Bckp\Translator
 */
class PluralProvider implements IPlural {
	/**
	 * Default plural detector (zero-one-other)
	 *
	 * @param int|null $n
	 * @return string
	 */
	public static function enPlural(?int $n): string {
		return ($n === 0 ? IPlural::ZERO : ($n === 1 ? IPlural::ONE : IPlural::OTHER));
	}

	/**
	 * No plural detector (zero-other)
	 *
	 * @param int $n
	 * @return string
	 */
	public static function zeroPlural(?int $n): string {
		return ($n === 0 ? IPlural::ZERO : IPlural::OTHER);
	}

	/**
	 * Czech plural selector (zero-one-few-other)
	 *
	 * @param int $n
	 * @return string
	 */
	public static function csPlural(?int $n): string {
		return ($n === 0 ? IPlural::ZERO : ($n === 1 ? IPlural::ONE : ($n >= 2 && $n < 5 ? IPlural::FEW : IPlural::OTHER)));
	}

	/**
	 * Get plural method
	 *
	 * @param string $locale
	 * @return callable
	 */
	public function getPlural(string $locale): callable {
		switch (strtolower($locale)) {
			default: // default zero-one-other
				return [$this, 'enPlural'];

			case 'id': // indonesian
			case 'ja': // japanese
			case 'ka': // georgian
			case 'ko': // korean
			case 'lo': // lao
			case 'ms': // malay
			case 'my': // burmese
			case 'th': // thai
			case 'vi': // vietnam
			case 'zh': // chinese (simplified)
				return [$this, 'zeroPlural'];

			case 'cs': // czech
				return [$this, 'csPlural'];

			//TODO: Add other languages plural selectors
		}
	}
}
