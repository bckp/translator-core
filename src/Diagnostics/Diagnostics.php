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

namespace Bckp\Translator\Diagnostics;

use Bckp\Translator\IDiagnostics;

/**
 * Class Diagnostics
 *
 * @package Bckp\Translator\Diagnostics
 */
class Diagnostics implements IDiagnostics {

	/**
	 * @var array
	 */
	private $messages = [];

	/**
	 * @var array
	 */
	private $untranslated = [];

	/**
	 * @var string
	 */
	private $locale = '';

	/**
	 * @param string $locale
	 */
	public function setLocale(string $locale): void {
		$this->locale = $locale;
	}

	/**
	 * @return string
	 */
	public function getLocale(): string{
		return $this->locale;
	}

	/**
	 * @param string $message
	 */
	public function warning(string $message): void {
		$this->messages[] = $message;
	}

	/**
	 * @param string $message
	 */
	public function untranslated(string $message): void {
		$this->untranslated[] = $message;
	}

	/**
	 * @return array
	 */
	public function getUntranslated(): array {
		return array_unique($this->untranslated);
	}

	/**
	 * @return array
	 */
	public function getWarnings(): array {
		return array_unique($this->messages);
	}
}
