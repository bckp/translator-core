<?php

declare(strict_types=1);

namespace Bckp\Translator\Storage;

use Bckp\Translator\Catalogue;
use Bckp\Translator\CatalogueDefinition;
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
	public function get(string $key): string|PluralMessage|null
	{
		return $this->messages->get($key);
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
