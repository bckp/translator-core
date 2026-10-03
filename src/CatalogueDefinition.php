<?php

declare(strict_types=1);

namespace Bckp\Translator;

final readonly class CatalogueDefinition
{
	public MessageCatalogue $messages;

	public function __construct(
		public string $locale,
		public int $build,
		public SourceVersions $versions,
		MessageCatalogue $messages,
		public PluralRule $pluralRule,
	) {
		$this->messages = clone $messages;
	}
}
