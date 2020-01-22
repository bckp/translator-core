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
 * Interface ICatalogue
 *
 * @package Bckp\Translator
 */
interface ICatalogue
{
    /**
     * Get build time in seconds
     * @return int
     */
    public function buildTime(): int;

    /**
     * Get the message translation
     * @param string $message
     * @return string|string[] return array if plural is detected
     */
    public function get(string $message);

    /**
     * Check if catalogue has message translation
     * @param string $message
     * @return bool
     */
    public function has(string $message): bool;

    /**
     * Get locale
     * @return string
     */
    public function locale(): string;

    /**
     * Plural form getter
     * @param int $n
     * @return string
     */
    public function plural(int $n): string;
}
