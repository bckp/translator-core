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

use Nette\Utils\Strings;
use function array_key_exists;
use function end;
use function gettype;
use function is_array;
use function is_object;
use function is_string;
use function key;
use function method_exists;
use function vsprintf;

/**
 * Class Translator
 *
 * @package Bckp\Translator
 */
class Translator implements ITranslator
{
    /** @var ICatalogue */
    private $catalogue;

    /** @var IDiagnostics|null */
    private $diagnostics;

    /** @var callable function(string $string): string */
    private $normalizeCallback;

    /**
     * Translator constructor.
     *
     * @param ICatalogue $catalogue
     * @param IDiagnostics|null $diagnostics
     */
    public function __construct(ICatalogue $catalogue, IDiagnostics $diagnostics = null)
    {
        $this->catalogue = $catalogue;
        $this->normalizeCallback = [$this, 'normalize'];
        if ($this->diagnostics = $diagnostics) {
            $this->diagnostics->setLocale($catalogue->locale());
        }
    }

    /**
     * Normalize string to preserve frameworks placeholders
     *
     * @param string $string
     * @return string
     */
    public function normalize(string $string): string
    {
        return str_replace(
            ['%label', '%value', '%name'],
            ['%%label', '%%value', '%%name'],
            $string
        );
    }

    /**
     * @param callable $callback
     * @return void
     */
    public function setNormalizeCallback(callable $callback): void
    {
        $this->normalizeCallback = $callback;
    }

    /**
     * @param mixed $message
     * @param mixed ...$parameters
     * @return string
     */
    public function translate($message, ...$parameters): string
    {
        if (empty($message)) {
            return '';
        }

        $form = null;
        $message = $this->getMessage($message, $form);
        if ($message === null) {
            return $this->warn('Expected string|array|object::__toString, but %s given.', gettype($message));
        }
        $result = $message;
        $this->expandParameters($parameters, $message);

        // process plural if any
        if ($translation = $this->catalogue->get($message)) {
            $result = $this->getVariant($message, $translation, $form);

            if ($parameters) {
                $result = ($this->normalizeCallback)($result);
                $result = @vsprintf($result, $parameters);
                // Intentionally @ as argument count can mismatch
            }
        } else {
            $this->untranslated((string)$message);
        }

        return $result;
    }

    /**
     * @param string $message
     * @param string|array<string> $translation
     * @param string|null $form
     * @return string
     */
    private function getVariant(string $message, $translation, string $form = null): string
    {
        if (!is_array($translation)) {
            return $translation;
        }

        if ($form === null || !array_key_exists($form, $translation)) {
            $this->warn(
                'Plural form not defined. (message: %s, form: %s)',
                (string)$message,
                (string)$form
            );
            end($translation);
            $form = key($translation);
        }
        return $translation[$form];
    }

    /**
     * @param mixed $message
     * @param string|null $plural
     * @return string|null
     */
    protected function getMessage($message, ?string &$plural): ?string
    {
        if (is_string($message)) {
            return $message;
        }

        if (is_array($message) && is_string($message[0])) {
            $plural = $this->catalogue->plural((int)$message[1] ?? 1);
            return $message[0];
        }

        if (is_object($message) && method_exists($message, '__toString')) {
            return (string)$message;
        }

        return null;
    }

    /**
     * @param string $message
     */
    protected function untranslated(string $message): void
    {
        if ($this->diagnostics !== null) {
            $this->diagnostics->untranslated($message);
        }
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

        if ($this->diagnostics !== null) {
            $this->diagnostics->warning($message);
        }

        return $message;
    }

    /**
     * @param array<mixed> $parameters
     * @param string|array<string|int> $message
     */
    private function expandParameters(array &$parameters, $message): void
    {
        if (
            empty($parameters)
            && is_array($message)
            && is_numeric($message[1] ?? null)
        ) {
            $parameters[] = $message[1];
        }
    }
}
