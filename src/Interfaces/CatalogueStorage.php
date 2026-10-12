<?php

declare(strict_types=1);

namespace Bckp\Translator\Interfaces;

use Bckp\Translator\Catalogue;
use Bckp\Translator\CatalogueDefinition;

interface CatalogueStorage
{
	public function load(string $key): ?Catalogue;

	public function save(string $key, CatalogueDefinition $definition): Catalogue;
}
