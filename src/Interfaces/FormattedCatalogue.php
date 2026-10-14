<?php

declare(strict_types=1);

namespace Bckp\Translator\Interfaces;

interface FormattedCatalogue
{
	public function format(string $key, float|int|string|null $number = null): ?string;
}
