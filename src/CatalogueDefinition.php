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

	public function normalizedMessages(): MessageCatalogue
	{
		$formats = new MessageCatalogue();
		$this->messages->each(static function (string $key, string|PluralMessage $message) use ($formats): void {
			if (is_string($message)) {
				$normalized = Translator::normalize($message);

				if ($normalized !== $message) {
					$formats->add($key, $normalized);
				}

				return;
			}
			$normalized = new PluralMessage(
				zero: $message->zero === null ? null : Translator::normalize($message->zero),
				one: $message->one === null ? null : Translator::normalize($message->one),
				two: $message->two === null ? null : Translator::normalize($message->two),
				few: $message->few === null ? null : Translator::normalize($message->few),
				many: $message->many === null ? null : Translator::normalize($message->many),
				other: $message->other === null ? null : Translator::normalize($message->other),
			);

			if ($normalized != $message) {
				$formats->add($key, $normalized);
			}
		});

		return $formats;
	}
}
