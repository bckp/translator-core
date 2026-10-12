<?php

declare(strict_types=1);

namespace Bckp\Translator;

use Closure;
use stdClass;

final class StringList
{
	private stdClass $values;

	public function __construct(string ...$values)
	{
		$this->values = new stdClass();
		foreach ($values as $value) {
			$this->add($value);
		}
	}

	public function add(string $value): void
	{
		$this->values->{$value} = $value;
	}

	public function contains(string $value): bool
	{
		return property_exists($this->values, $value);
	}

	public function first(): ?string
	{
		foreach (get_object_vars($this->values) as $value) {
			return $value;
		}

		return null;
	}

	public function count(): int
	{
		return count(get_object_vars($this->values));
	}

	public function isEmpty(): bool
	{
		return $this->count() === 0;
	}

	public function join(string $separator): string
	{
		return implode($separator, get_object_vars($this->values));
	}

	public function each(Closure $consumer): void
	{
		foreach (get_object_vars($this->values) as $value) {
			$consumer($value);
		}
	}

	public function __clone(): void
	{
		$this->values = clone $this->values;
	}
}
