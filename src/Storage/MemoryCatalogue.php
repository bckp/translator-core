<?php

declare(strict_types=1);

namespace Bckp\Translator\Storage;

use Bckp\Translator\Catalogue;
use Bckp\Translator\CatalogueDefinition;
use Bckp\Translator\Interfaces\FormattedCatalogue;
use Bckp\Translator\MessageCatalogue;
use Bckp\Translator\Plural;
use Bckp\Translator\PluralMessage;
use Bckp\Translator\PluralRule;

use function is_string;

final class MemoryCatalogue extends Catalogue implements FormattedCatalogue
{
	private readonly MessageCatalogue $messages;

	private readonly MessageCatalogue $formats;

	private readonly PluralRule $rule;

	public function __construct(CatalogueDefinition $definition)
	{
		parent::__construct($definition->locale, $definition->build, $definition->versions);
		$this->messages = clone $definition->messages;
		$this->formats = $definition->normalizedMessages();
		$this->rule = $definition->pluralRule;
	}

	#[\Override]
	public function get(string $key, float|int|string|null $number = null): ?string
	{
		$message = $this->messages->get($key);

		if (!$message instanceof PluralMessage) {
			return $message;
		}
		$plural = is_numeric($number) ? $this->plural((int) $number) : Plural::Other;

		return $message->select($plural) ?? $message->fallback;
	}

	#[\Override]
	public function missingPluralForm(string $key, float|int|string|null $number = null): ?Plural
	{
		$message = $this->messages->get($key);

		if (!$message instanceof PluralMessage) {
			return null;
		}
		$plural = is_numeric($number) ? $this->plural((int) $number) : Plural::Other;

		return $message->select($plural) === null ? $plural : null;
	}

	#[\Override]
	public function format(string $key, float|int|string|null $number = null): ?string
	{
		$message = $this->formats->get($key);

		if ($message === null) {
			return $this->get($key, $number);
		}

		if (is_string($message)) {
			return $message;
		}
		$plural = is_numeric($number) ? $this->plural((int) $number) : Plural::Other;

		return $message->select($plural) ?? $message->fallback;
	}

	#[\Override]
	public function has(string $key): bool
	{
		return $this->messages->has($key);
	}

	#[\Override]
	public function plural(int $number): Plural
	{
		return $this->rule->select($number);
	}
}
