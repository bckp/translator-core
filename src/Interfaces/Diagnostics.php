<?php

declare(strict_types=1);

/**
 * BCKP Translator
 * (c) Radovan Kepák
 *
 * For the full copyright and license information, please view
 * the file license.md that was distributed with this source code.
 *
 * @author Radovan Kepak <radovan@kepak.dev>
 */

namespace Bckp\Translator\Interfaces;

interface Diagnostics
{
	public function setLocale(string $locale): void;
	public function untranslated(string $message): void;
	public function warning(string $message): void;
}
