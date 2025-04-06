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

use Bckp\Translator\Interfaces;

use function array_unique;

class Diagnostics implements Interfaces\Diagnostics
{
    /** @var string */
    private string $locale = '';

    /** @var array<string> */
    private array $messages = [];

    /** @var array<string> */
    private array $untranslated = [];

    public function getLocale(): string
    {
        return $this->locale;
    }

    /**
     * @return string[]
     */
    public function getUntranslated(): array
    {
        return array_unique($this->untranslated);
    }

    /**
     * @return string[]
     */
    public function getWarnings(): array
    {
        return array_unique($this->messages);
    }

    public function setLocale(string $locale): void
    {
        $this->locale = $locale;
    }

    public function untranslated(string $message): void
    {
        $this->untranslated[] = $message;
    }

    public function warning(string $message): void
    {
        $this->messages[] = $message;
    }
}
