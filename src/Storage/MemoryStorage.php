<?php

declare(strict_types=1);

namespace Bckp\Translator\Storage;

use Bckp\Translator\Catalogue;
use Bckp\Translator\CatalogueDefinition;
use Bckp\Translator\Interfaces\CatalogueStorage;
use stdClass;

final class MemoryStorage implements CatalogueStorage
{
	private stdClass $catalogues;

	public function __construct()
	{
		$this->catalogues = new stdClass();
	}

	#[\Override]
	public function load(string $key): ?Catalogue
	{
		return $this->catalogues->{$key} ?? null;
	}

	#[\Override]
	public function save(string $key, CatalogueDefinition $definition): Catalogue
	{
		$catalogue = new MemoryCatalogue($definition);
		$this->catalogues->{$key} = $catalogue;

		return $catalogue;
	}
}
