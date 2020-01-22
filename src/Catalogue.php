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
abstract class Catalogue implements ICatalogue
{
    /** @var array<string|array> */
    protected static $messages;

    /** @var int */
    protected $build;

    /** @var string */
    protected $locale;

    /**
     * Get build time
     *
     * @return int
     */
    public function buildTime(): int
    {
        return $this->build;
    }

    /**
     * Get the message translation
     *
     * @param string $message
     * @return string|array<string> return array if plural is detected
     */
    public function get(string $message)
    {
        return static::$messages[$message] ?? '';
    }

    /**
     * Check if catalogue has message translation
     *
     * @param string $message
     * @return bool
     */
    public function has(string $message): bool
    {
        return isset(static::$messages[$message]);
    }

    /**
     * @return string
     */
    public function locale(): string
    {
        return $this->locale;
    }

    /**
     * Plural form getter
     *
     * @param int $n
     * @return string
     */
    abstract public function plural(int $n): string;
}
