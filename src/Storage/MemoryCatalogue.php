<?php

declare(strict_types=1);

namespace Bckp\Translator\Storage;

use Bckp\Translator\Catalogue;
use Bckp\Translator\CatalogueDefinition;
use Bckp\Translator\Interfaces\Diagnostics;
use Bckp\Translator\MessageCatalogue;
use Bckp\Translator\Plural;
use Bckp\Translator\PluralMessage;
use Bckp\Translator\PluralRule;

final class MemoryCatalogue extends Catalogue
{
	private readonly MessageCatalogue $messages;

	private readonly PluralRule $rule;

	public function __construct(CatalogueDefinition $definition)
	{
		parent::__construct($definition->locale, $definition->build, $definition->versions);
		$this->messages = clone $definition->messages;
		$this->rule = $definition->pluralRule;
	}

	#[\Override]
	public function get(string $key, float|int|string|null $number = null, ?Diagnostics $diagnostics = null): ?string
	{
		$message = $this->messages->get($key);

		if (!$message instanceof PluralMessage) {
			return $message;
		}
		$plural = is_numeric($number) ? $this->plural((int) $number) : Plural::Other;
		$translation = $message->select($plural);

		if ($translation === null) {
			$diagnostics?->warning("Plural form not defined. (message: $key, form: {$plural->value})");
		}

		return $translation ?? $message->fallback;
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
