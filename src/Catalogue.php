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
 * Class Catalogue
 *
 * @package Bckp\Translator
 */
abstract class Catalogue implements ICatalogue {

	/**
	 * @var array
	 */
	protected static $messages;

	/**
	 * @var int
	 */
	protected $build;

	/**
	 * @var string
	 */
	protected $locale;

	/**
	 * Plural form getter
	 *
	 * @param int $n
	 * @return string
	 */
	abstract function plural(int $n): string;

	/**
	 * @return string
	 */
	public function locale(): string{
		return $this->locale;
	}

	/**
	 * Check if catalogue has message translation
	 *
	 * @param string $message
	 * @return bool
	 */
	public function has(string $message): bool {
		return isset(static::$messages[$message]);
	}

	/**
	 * Get the message translation
	 *
	 * @param string $message
	 * @return string|array return array if plural is detected
	 */
	public function get(string $message) {
		return static::$messages[$message] ?? '';
	}

	/**
	 * Get build time
	 *
	 * @return int
	 */
	public function buildTime(): int {
		return $this->build;
	}
}
