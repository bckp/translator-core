<?php

declare(strict_types=1);

namespace Bckp\Translator;

use Bckp\Translator\Interfaces\CatalogueStorage;
use Bckp\Translator\Interfaces\Diagnostics;
use Bckp\Translator\Interfaces\TranslationSource;
use InvalidArgumentException;
use LogicException;
use stdClass;

final class CatalogueBuilder
{
	private stdClass $sources;

	private ?Catalogue $catalogue = null;

	private bool $debugMode = false;

	private float $checkProbability = 0.01;

	private readonly string $locale;

	public function __construct(
		private readonly CatalogueStorage $storage,
		string $locale,
		private readonly PluralProvider $pluralProvider = new PluralProvider(),
		private readonly string $namespace = 'default',
		private readonly ?Diagnostics $diagnostics = null,
	) {
		$this->locale = Locale::normalize($locale);
		$this->sources = new stdClass();
	}

	public function getLocale(): string
	{
		return $this->locale;
	}

	public function addSource(string $id, TranslationSource $source): self
	{
		if ($this->catalogue !== null) {
			throw new LogicException('Sources cannot be changed after the catalogue is initialized.');
		}

		if ($id === '' || property_exists($this->sources, $id)) {
			throw new InvalidArgumentException("Empty or duplicate source identifier: $id");
		}
		$this->sources->{$id} = $source;

		return $this;
	}

	public function setDebugMode(bool $debugMode): self
	{
		$this->debugMode = $debugMode;

		return $this;
	}

	public function setCheckProbability(float $probability): self
	{
		if (!is_finite($probability) || $probability < 0 || $probability > 1) {
			throw new InvalidArgumentException('Check probability must be between 0 and 1.');
		}
		$this->checkProbability = $probability;

		return $this;
	}

	public function compile(): Catalogue
	{
		if ($this->catalogue !== null) {
			return $this->catalogue;
		}

		$rule = $this->pluralProvider->getRule($this->locale);
		$key = $this->cacheKey($rule);
		$catalogue = $this->storage->load($key);

		if ($catalogue === null || $catalogue->locale !== $this->locale) {
			return $this->build($key, $rule, $this->versions());
		}

		if (!$this->shouldCheck()) {
			return $this->useCatalogue($catalogue, checked: false, recompiled: false);
		}

		$versions = $this->versions();

		if ($catalogue->versions->equals($versions)) {
			return $this->useCatalogue($catalogue, checked: true, recompiled: false);
		}

		return $this->build($key, $rule, $versions);
	}

	public function forceRecompile(): Catalogue
	{
		$rule = $this->pluralProvider->getRule($this->locale);

		return $this->build($this->cacheKey($rule), $rule, $this->versions());
	}

	private function shouldCheck(): bool
	{
		if ($this->debugMode || $this->checkProbability === 1.0) {
			return true;
		}

		if ($this->checkProbability === 0.0) {
			return false;
		}

		$sample = mt_rand() / (mt_getrandmax() + 1);

		return $sample < $this->checkProbability;
	}

	private function cacheKey(PluralRule $rule): string
	{
		return hash('sha256', serialize([
			'format' => 3,
			'namespace' => $this->namespace,
			'locale' => $this->locale,
			'rule' => $rule->value,
			'sources' => array_keys(get_object_vars($this->sources)),
		]));
	}

	private function versions(): SourceVersions
	{
		$versions = [];
		foreach (get_object_vars($this->sources) as $id => $source) {
			if (!$source instanceof TranslationSource) {
				throw new LogicException('Invalid source registration.');
			}
			$versions[] = new SourceVersion((string) $id, $source->getVersion($this->locale));
		}

		return new SourceVersions(...$versions);
	}

	private function build(string $key, PluralRule $rule, SourceVersions $versions): Catalogue
	{
		$definition = new CatalogueDefinition(
			locale: $this->locale,
			build: time(),
			versions: $versions,
			messages: $this->messages(),
			pluralRule: $rule,
		);
		$catalogue = $this->storage->save($key, $definition);

		if ($catalogue->locale !== $this->locale || !$catalogue->versions->equals($versions)) {
			throw new LogicException('Storage returned an incompatible catalogue.');
		}

		return $this->useCatalogue($catalogue, checked: true, recompiled: true);
	}

	private function messages(): MessageCatalogue
	{
		$messages = new MessageCatalogue();
		foreach (get_object_vars($this->sources) as $source) {
			if (!$source instanceof TranslationSource) {
				throw new LogicException('Invalid source registration.');
			}
			$messages->merge($source($this->locale));
		}

		return $messages;
	}

	private function useCatalogue(Catalogue $catalogue, bool $checked, bool $recompiled): Catalogue
	{
		$this->catalogue = $catalogue;
		$this->diagnostics?->catalogueUsed(new CatalogueStatus($catalogue, $checked, $recompiled));

		return $catalogue;
	}
}
