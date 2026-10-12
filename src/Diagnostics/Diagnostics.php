<?php

declare(strict_types=1);

namespace Bckp\Translator\Diagnostics;

use Bckp\Translator\CatalogueStatus;
use Bckp\Translator\Interfaces;
use Bckp\Translator\StringList;
use Closure;
use stdClass;

class Diagnostics implements Interfaces\Diagnostics
{
	private string $locale = '';

	private StringList $warnings;

	private StringList $untranslated;

	private stdClass $catalogues;

	public function __construct()
	{
		$this->warnings = new StringList();
		$this->untranslated = new StringList();
		$this->catalogues = new stdClass();
	}

	public function getLocale(): string
	{
		return $this->locale;
	}

	public function getWarnings(): StringList
	{
		return clone $this->warnings;
	}

	public function getUntranslated(): StringList
	{
		return clone $this->untranslated;
	}

	public function eachCatalogue(Closure $consumer): void
	{
		foreach (get_object_vars($this->catalogues) as $status) {
			$consumer($status);
		}
	}

	#[\Override]
	public function setLocale(string $locale): void
	{
		$this->locale = $locale;
	}

	#[\Override]
	public function untranslated(string $message): void
	{
		$this->untranslated->add($message);
	}

	#[\Override]
	public function warning(string $message): void
	{
		$this->warnings->add($message);
	}

	#[\Override]
	public function catalogueUsed(CatalogueStatus $status): void
	{
		$this->catalogues->{$status->catalogue->locale} = $status;
	}
}
