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
 * Interface ITranslator
 *
 * @package Bckp\Translator
 */
interface ITranslator {
	/**
	 * @param array|string|object $message
	 * @param mixed ...$params
	 * @return string
	 */
	public function translate($message, ...$params): string;
}
