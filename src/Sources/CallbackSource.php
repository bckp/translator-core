<?php

declare(strict_types=1);

namespace Bckp\Translator\Sources;

use Bckp\Translator\Interfaces\TranslationSource;
use Bckp\Translator\MessageCatalogue;
use Closure;
use UnexpectedValueException;

final readonly class CallbackSource implements TranslationSource
{
	public function __construct(
		private Closure $loader,
		private string|Closure $version,
	) {
	}

	#[\Override]
	public function getVersion(string $locale): string
	{
		$version = $this->version instanceof Closure ? ($this->version)($locale) : $this->version;

		if (!is_string($version)) {
			throw new UnexpectedValueException('A source version must be a string.');
		}

		return $version;
	}

	#[\Override]
	public function __invoke(string $locale): MessageCatalogue
	{
		$catalogue = ($this->loader)($locale);

		if (!$catalogue instanceof MessageCatalogue) {
			throw new UnexpectedValueException('A translation source must return MessageCatalogue.');
		}

		return $catalogue;
	}
}
