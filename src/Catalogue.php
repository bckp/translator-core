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
    public readonly int $build;
    public readonly string $locale;

	/** @var array<string|array<string, string>> */
	protected static array $messages;

    public function __construct(string $locale, int $build) {
        $this->build = $build;
        $this->locale = $locale;
    }

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
     * @deprecated use property $locale instead
	 */
	public function locale(): string {
        return $this->locale;
    }

	/**
	 * @api
     * @deprecated use property $build instead
	 */
	public function build(): int {
        return $this->build;
    }
}
