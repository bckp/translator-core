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

/**
 * Class Catalogue
 *
 * @package Bckp\Translator
 */
abstract class Catalogue
{
    /** @var array<string|array> */
    protected static array $messages;

    /**
     * @return string|string[]
     */
    public function get(string $message): array|string
    {
        return static::$messages[$message] ?? '';
    }

    public function has(string $message): bool
    {
        return array_key_exists($message, static::$messages);
    }

    abstract public function plural(int $n): Plural;
    abstract public function locale(): string;
    abstract public function build(): int;
}
