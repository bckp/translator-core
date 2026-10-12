<?php

declare(strict_types=1);

namespace Bckp\Translator;

use Closure;
use stdClass;

final class MessageCatalogue
{
	private stdClass $messages;

	public function __construct()
	{
		$this->messages = new stdClass();
	}

	public function add(string $key, string|PluralMessage $message): self
	{
		$this->messages->{$key} = $message;

		return $this;
	}

	public function get(string $key): string|PluralMessage|null
	{
		return $this->messages->{$key} ?? null;
	}

	public function has(string $key): bool
	{
		return property_exists($this->messages, $key);
	}

	public function merge(self $catalogue): void
	{
		foreach (get_object_vars($catalogue->messages) as $key => $message) {
			$this->messages->{(string) $key} = $message;
		}
	}

	public function each(Closure $consumer): void
	{
		foreach (get_object_vars($this->messages) as $key => $message) {
			$consumer((string) $key, $message);
		}
	}

	public function count(): int
	{
		return count(get_object_vars($this->messages));
	}

	public function __clone(): void
	{
		$this->messages = clone $this->messages;
	}
}
