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

namespace Bckp\Translator;

abstract class Catalogue
{
	/** @var array<string|array<string, string>> */
	protected static array $messages;

	/**
	 * @api
	 * @return string|array<string, string>
	 */
	public function get(string $message): array|string
	{
		return static::$messages[$message] ?? '';
	}

	/**
	 * @api
	 */
	public function has(string $message): bool
	{
		return array_key_exists($message, static::$messages);
	}

	/**
	 * @api
	 */
	abstract public function plural(int $n): Plural;

	/**
	 * @api
	 */
	abstract public function locale(): string;

	/**
	 * @api
	 */
	abstract public function build(): int;
}
