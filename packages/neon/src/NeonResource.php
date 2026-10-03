<?php

declare(strict_types=1);

namespace Bckp\Translator\Neon;

final readonly class NeonResource
{
	public function __construct(public string $path, public string $prefix) {}
}
