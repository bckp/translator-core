<?php

declare(strict_types=1);

namespace Bckp\Translator;

final readonly class SourceVersion
{
	public function __construct(public string $id, public string $version) {}
}
