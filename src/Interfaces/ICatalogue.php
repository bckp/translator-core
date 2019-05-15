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
 * Interface ICatalogue
 *
 * @package Bckp\Translator
 */
interface ICatalogue {

	/**
	 * Plural form getter
	 * @param int $n
	 * @return string
	 */
	public function plural(int $n): string;

	/**
	 * Get locale
	 * @return string
	 */
	public function locale(): string;

	/**
	 * Check if catalogue has message translation
	 * @param string $message
	 * @return bool
	 */
	public function has(string $message): bool;

	/**
	 * Get the message translation
	 * @param string $message
	 * @return string|array return array if plural is detected
	 */
	public function get(string $message);

	/**
	 * Get build time in seconds
	 * @return int
	 */
	public function buildTime(): int;
}
