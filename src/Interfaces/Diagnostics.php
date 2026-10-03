<?php

declare(strict_types=1);

namespace Bckp\Translator\Interfaces;

use Bckp\Translator\CatalogueStatus;

interface Diagnostics
{
	public function setLocale(string $locale): void;

	public function untranslated(string $message): void;

	public function warning(string $message): void;

	public function catalogueUsed(CatalogueStatus $status): void;
}
