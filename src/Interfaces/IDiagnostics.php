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
 * Interface IDiagnostics
 *
 * @package Bckp\Translator
 */
interface IDiagnostics
{
    /**
     * @param string $locale
     */
    public function setLocale(string $locale): void;

    /**
     * @param string $message
     */
    public function untranslated(string $message): void;

    /**
     * @param string $message
     */
    public function warning(string $message): void;
}
