<?php

declare(strict_types=1);

namespace Bckp\Translator\Interfaces;

use Bckp\Translator\MessageCatalogue;

interface TranslationSource
{
	public function getVersion(string $locale): string;

	public function __invoke(string $locale): MessageCatalogue;
}
