<?php

declare(strict_types=1);

namespace Bckp\Translator\Sources;

use Bckp\Translator\Interfaces\TranslationSource;
use Bckp\Translator\MessageCatalogue;

final readonly class StaticSource implements TranslationSource
{
	private MessageCatalogue $messages;

	public function __construct(MessageCatalogue $messages, private string $version)
	{
		$this->messages = clone $messages;
	}

	#[\Override]
	public function getVersion(string $locale): string
	{
		return $this->version;
	}

	#[\Override]
	public function __invoke(string $locale): MessageCatalogue
	{
		return clone $this->messages;
	}
}
