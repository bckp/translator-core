<?php

declare(strict_types=1);

namespace Bckp\Translator\Interfaces;

use Bckp\Translator\Catalogue;
use Closure;
use Stringable;

interface Translator
{
	public function getLocale(): string;

	public function translate(string|Stringable $message, float|int|string ...$parameters): string;

	public function setNormalizeCallback(Closure $callback): void;

	public function replaceCatalogue(Catalogue $catalogue): void;
}
