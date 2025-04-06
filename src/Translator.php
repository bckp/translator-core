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

use Bckp\Translator\Interfaces\Diagnostics;
use Stringable;
use function array_key_exists;
use function array_key_last;
use function is_array;
use function vsprintf;

/**
 * Class Translator
 *
 * @package Bckp\Translator
 */
class Translator implements Interfaces\Translator
{
    /** @var callable function(string $string): string */
    private $normalizeCallback;

    public function __construct(
        private readonly Catalogue $catalogue,
        private readonly ?Diagnostics $diagnostics = null
    ) {
        $this->normalizeCallback = [$this, 'normalize'];
        $this->diagnostics?->setLocale($catalogue->locale());
    }

    public function normalize(string $string): string
    {
        return str_replace(
            ['%label', '%value', '%name'],
            ['%%label', '%%value', '%%name'],
            $string
        );
    }

    public function setNormalizeCallback(callable $callback): void
    {
        $this->normalizeCallback = $callback;
    }

    public function translate(string|Stringable $message, mixed ...$parameters): string
    {
        $message = (string) $message;

        if (empty($message)) {
            return '';
        }

        $translation = $this->catalogue->get($message);
        if (!$translation) {
            $this->untranslated($message);
            return $message;
        }

        // Plural option is returned, we need to choose the right one
        if (is_array($translation)) {
            $plural = is_numeric($parameters[0] ?? null) ? $this->catalogue->plural((int) $parameters[0]) : Plural::Other;
            $translation = $this->getVariant($message, $translation, $plural);
        }

        if ($parameters) {
            $translation = ($this->normalizeCallback)($translation);
            $translation = @vsprintf($translation, $parameters);
        }

        return $translation;
    }

    public function getVariant(string $message, array $translations, Plural $plural): string
    {
        if (!array_key_exists($plural->value, $translations)) {
            $this->warn(
                'Plural form not defined. (message: %s, form: %s)',
                $message,
                $plural->value,
            );
        }

        return $translations[$plural->value] ?? $translations[array_key_last($translations)];
    }

    /**
     * @param string $message
     */
    protected function untranslated(string $message): void
    {
        $this->diagnostics?->untranslated($message);
    }

    /**
     * @param string $message
     * @param mixed ...$parameters
     * @return string
     */
    protected function warn(string $message, ...$parameters): string
    {
        if (!empty($parameters)) {
            $message = @vsprintf($message, $parameters);
        } // Intentionally @ as parameter count can mismatch

        $this->diagnostics?->warning($message);

        return $message;
    }
}
