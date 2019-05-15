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

use Nette\FileNotFoundException;
use Nette\InvalidStateException;
use RuntimeException;

class PathInvalidException extends FileNotFoundException {
}

class FileInvalidException extends InvalidStateException {
}

class TranslatorException extends RuntimeException {
}

class BuilderException extends TranslatorException {
}
