<?php declare(strict_types=1);
/**
 * BCKP Translator
 * (c) Radovan Kepák
 *
 * For the full copyright and license information, please view the file license.md that was distributed with this source code.
 *
 * @author Radovan Kepak <radovan@kepak.eu>
 *  --------------------------------------------------------------------------
 */

namespace Bckp\Translator;

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
class Translator implements ITranslator {
	/**
	 * @var ICatalogue
	 */
	private $catalogue;

	/**
	 * @var IDiagnostics|null
	 */
	private $diagnostics;

	/**
	 * Translator constructor.
	 *
	 * @param ICatalogue $catalogue
	 * @param IDiagnostics|null $diagnostics
	 */
	public function __construct(ICatalogue $catalogue, IDiagnostics $diagnostics = null) {
		$this->catalogue = $catalogue;
		if ($this->diagnostics = $diagnostics) {
			$this->diagnostics->setLocale($catalogue->locale());
		}
	}

	/**
	 * @param mixed $message
	 * @param mixed ...$parameters
	 * @return string
	 */
	public function translate($message, ...$parameters): string {
		// html and empty are returned without processing
		if (empty($message) || $message instanceof \Nette\Utils\Html)
			return (string) $message;

		// expand parameters
		if (!$parameters && is_array($message) && is_numeric($message[1] ?? null))
			$parameters[] = $message[1];

		// get message and plural if any
		$form = null;
		$message = $this->getMessage($message, $form);

		// check message to be string
		if (!is_string($message)) {
			return $this->warn('Expected string|array|object::__toString, but %s given.', gettype($message));
		}

		// process plural if any
		$result = $message;
		if ($translation = $this->catalogue->get($message)) {
			// plural
			if (is_array($translation)) {
				if (!array_key_exists($form, $translation) || $form === null) {
					$this->warn('Plural form not defined. (message: %s, form: %s)', (string) $message, (string) $form);
					end($translation);
					$form = key($translation);
				}

				$result = $translation[$form];
			} elseif (is_string($translation)) {
				$result = $translation;
			}

			if ($parameters) {
				$result = @vsprintf($this->normalize($result), $parameters); // Intentionally @ as argument count can mismatch
			}
		} else {
			$this->untranslated((string) $message);
		}

		return $result;
	}

	/**
	 * @param mixed $message
	 * @param string|null $plural
	 * @return string|null
	 */
	protected function getMessage($message, ?string &$plural): ?string {
		if (is_string($message))
			return $message;

		if (is_array($message) && is_string($message[0])) {
			$plural = $this->catalogue->plural((int) $message[1] ?? null);
			return $message[0];
		}

		if (is_object($message) && method_exists($message, '__toString'))
			return (string) $message;

		return null;
	}

	/**
	 * @param string $message
	 * @param mixed ...$parameters
	 * @return string
	 */
	protected function warn(string $message, ...$parameters): string {
		if ($parameters)
			$message = @vsprintf($message, $parameters); // Intentionally @ as parameter count can mismatch

		if ($this->diagnostics !== null)
			$this->diagnostics->warning($message);

		return $message;
	}

	/**
	 * Normalize string to preserve nette placeholders
	 *
	 * @param string $string
	 * @return string
	 */
	protected function normalize(string $string): string {
		return (string) str_replace(['%label', '%value', '%name'], ['%%label', '%%value', '%%name'], $string);
	}

	/**
	 * @param string $message
	 */
	protected function untranslated(string $message): void {
		if ($this->diagnostics !== null)
			$this->diagnostics->untranslated($message);
	}
}
