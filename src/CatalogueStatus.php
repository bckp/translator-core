<?php

declare(strict_types=1);

namespace Bckp\Translator;

final readonly class CatalogueStatus
{
	public function __construct(
		public Catalogue $catalogue,
		public bool $checked,
		public bool $recompiled,
	) {
	}
}
