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

namespace Bckp\Translator\Interfaces;

use Stringable;

/**
 * Interface ITranslator
 *
 * @package Bckp\Translator
 */
interface Translator
{
	/**
	 * @param callable $callback function(string $string): string
	 */
	public function setNormalizeCallback(callable $callback): void;

	public function translate(string|Stringable $message, mixed ...$params): string;
}
