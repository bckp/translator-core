<?php

declare(strict_types=1);

/**
 * BCKP Translator
 * (c) Radovan Kepák
 *
 * For the full copyright and license information, please view
 * the file license.md that was distributed with this source code.
 *
 * @author Radovan Kepak <radovan@kepak.dev>
 */

namespace Bckp\Translator;

use Bckp\Translator\Exceptions\BuilderException;
use Bckp\Translator\Exceptions\TranslatorException;

use function strtolower;

/**
 * @api
 */
class TranslatorProvider
{
	/**
	 * @var array<string, CatalogueBuilder>
	 */
	protected array $catalogues = [];

	/**
	 * @var array<string, Interfaces\Translator>
	 */
	protected array $translators = [];

	/**
	 * @param array<string> $languages
	 */
	public function __construct(
		protected array $languages,
		protected ?Interfaces\Diagnostics $diagnostics = null
	) {}

	public function addCatalogue(
		string $locale,
		CatalogueBuilder $builder
	): void {
		$this->catalogues[strtolower($locale)] = $builder;
	}

	public function getTranslator(string $locale): Interfaces\Translator
	{
		$locale = strtolower($locale);
		if (!isset($this->translators[$locale])) {
			$this->translators[$locale] = $this->createTranslator($locale);
		}

		return $this->translators[$locale];
	}

	/**
	 * @throws BuilderException
	 */
	protected function createTranslator(string $locale): Interfaces\Translator
	{
		if (!isset($this->catalogues[$locale])) {
			throw new TranslatorException("Language {$locale} requested, but corresponding catalogue missing.");
		}

		return new Translator(
			$this->catalogues[$locale]->compile(),
			$this->diagnostics
		);
	}
}
