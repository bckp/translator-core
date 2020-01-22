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

namespace Bckp\Translator\Diagnostics;

use Bckp\Translator\IDiagnostics;

use function array_unique;

/**
 * Class Diagnostics
 *
 * @package Bckp\Translator\Diagnostics
 */
class Diagnostics implements IDiagnostics
{
    /** @var string */
    private $locale = '';

    /** @var array<string> */
    private $messages = [];

    /** @var array<string> */
    private $untranslated = [];

    /**
     * @return string
     */
    public function getLocale(): string
    {
        return $this->locale;
    }

    /**
     * @return array<string>
     */
    public function getUntranslated(): array
    {
        return array_unique($this->untranslated);
    }

    /**
     * @return array<string>
     */
    public function getWarnings(): array
    {
        return array_unique($this->messages);
    }

    /** @param string $locale */
    public function setLocale(string $locale): void
    {
        $this->locale = $locale;
    }

    /**
     * @param string $message
     */
    public function untranslated(string $message): void
    {
        $this->untranslated[] = $message;
    }

    /**
     * @param string $message
     */
    public function warning(string $message): void
    {
        $this->messages[] = $message;
    }
}
