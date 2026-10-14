<?php

declare(strict_types=1);

namespace Bckp\Translator;

use Closure;
use UnexpectedValueException;

final readonly class CatalogueDefinition
{
	public MessageCatalogue $messages;

	public function __construct(
		public string $locale,
		public int $build,
		public SourceVersions $versions,
		MessageCatalogue $messages,
		public PluralRule $pluralRule,
		private ?Closure $normalizer = null,
	) {
		$this->messages = clone $messages;
	}

	public function normalizedMessages(): MessageCatalogue
	{
		$formats = new MessageCatalogue();
		$normalizer = $this->normalizer ?? Translator::normalize(...);
		$this->messages->each(static function (string $key, string|PluralMessage $message) use ($formats, $normalizer): void {
			if (is_string($message)) {
				$normalized = self::normalize($normalizer, $message);

				if ($normalized !== $message) {
					$formats->add($key, $normalized);
				}

				return;
			}
			$normalized = new PluralMessage(
				zero: $message->zero === null ? null : self::normalize($normalizer, $message->zero),
				one: $message->one === null ? null : self::normalize($normalizer, $message->one),
				two: $message->two === null ? null : self::normalize($normalizer, $message->two),
				few: $message->few === null ? null : self::normalize($normalizer, $message->few),
				many: $message->many === null ? null : self::normalize($normalizer, $message->many),
				other: $message->other === null ? null : self::normalize($normalizer, $message->other),
			);

			if ($normalized != $message) {
				$formats->add($key, $normalized);
			}
		});

		return $formats;
	}

	private static function normalize(Closure $normalizer, string $text): string
	{
		$normalized = $normalizer($text);

		if (!is_string($normalized)) {
			throw new UnexpectedValueException('The normalize callback must return a string.');
		}

		return $normalized;
	}
}
