<?php

declare(strict_types=1);

namespace Bckp\Translator;

abstract class Catalogue
{
	public function __construct(
		public readonly string $locale,
		public readonly int $build,
		public readonly SourceVersions $versions,
	) {}

	abstract public function get(string $key): string|PluralMessage|null;

	abstract public function has(string $key): bool;

	abstract public function plural(int $number): Plural;
}
