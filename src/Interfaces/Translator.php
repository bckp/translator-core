<?php

declare(strict_types=1);

/**
 * BCKP Translator
 * (c) Radovan Kepák
 *
 * For the full copyright and license information, please view
 * the file license.md that was distributed with this source code.
 *
 * @author Radovan Kepak <radovan@kepak.dev>
 */

namespace Bckp\Translator\Interfaces;

use Stringable;

interface Translator
{
	/**
	 * @api
	 * @param callable $callback function(string $string): string
	 */
	public function setNormalizeCallback(callable $callback): void;

	/**
	 * @api
	 */
	public function getLocale(): string;

	/**
	 * @api
	 */
	public function translate(string|Stringable $message, float|int|string ...$parameters): string;
}
