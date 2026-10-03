<?php

declare(strict_types=1);

namespace Bckp\Translator\Tests;

use Bckp\Translator\Interfaces\TranslationSource;
use Bckp\Translator\MessageCatalogue;
use RuntimeException;

final class CountingSource implements TranslationSource
{
	public int $versionCalls = 0;

	public int $loadCalls = 0;

	public bool $fail = false;

	public function __construct(public string $version = 'v1', public string $text = 'Hello')
	{
	}

	#[\Override]
	public function getVersion(string $locale): string
	{
		++$this->versionCalls;

		return $this->version;
	}

	#[\Override]
	public function __invoke(string $locale): MessageCatalogue
	{
		++$this->loadCalls;

		if ($this->fail) {
			throw new RuntimeException('Source failed.');
		}

		return (new MessageCatalogue())->add('messages.hello', $this->text);
	}
}
