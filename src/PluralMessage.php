<?php

declare(strict_types=1);

namespace Bckp\Translator;

use InvalidArgumentException;

final readonly class PluralMessage
{
	public string $fallback;

	public function __construct(
		public ?string $zero = null,
		public ?string $one = null,
		public ?string $two = null,
		public ?string $few = null,
		public ?string $many = null,
		public ?string $other = null,
	) {
		$this->fallback = $other ?? $many ?? $few ?? $two ?? $one ?? $zero
			?? throw new InvalidArgumentException('A plural message needs at least one variant.');
	}

	public function select(Plural $plural): ?string
	{
		return match ($plural) {
			Plural::Zero => $this->zero,
			Plural::One => $this->one,
			Plural::Two => $this->two,
			Plural::Few => $this->few,
			Plural::Many => $this->many,
			Plural::Other => $this->other,
		};
	}
}
