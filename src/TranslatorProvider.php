<?php

/**
 * BCKP Translator
 * (c) Radovan Kepák
 *
 * For the full copyright and license information, please view
 * the file license.md that was distributed with this source code.
 *
 * @author Radovan Kepak <radovan@kepak.eu>
 */

declare(strict_types=1);

namespace Bckp\Translator;

use Bckp\Translator\Builder\Catalogue as BuilderCatalogue;

use function strtolower;

/**
 * Class TranslatorProvider
 *
 * @package Bckp\Translator
 */
class TranslatorProvider
{
    /** @var BuilderCatalogue[] */
    protected $catalogues = [];

    /** @var IDiagnostics|null */
    protected $diagnostics = null;

    /** @var string[] */
    protected $languages = [];

    /** @var ITranslator[] */
    protected $translators = [];

    /**
     * TranslatorProvider constructor.
     *
     * @param string[] $languages
     * @param IDiagnostics|null $diagnostics
     */
    public function __construct(array $languages, IDiagnostics $diagnostics = null)
    {
        $this->diagnostics = $diagnostics;
        $this->languages = $languages;
    }

    /**
     * @param string $locale
     * @param BuilderCatalogue $builder
     * @return void
     */
    public function addCatalogue(string $locale, BuilderCatalogue $builder): void
    {
        $locale = strtolower($locale);
        $this->catalogues[$locale] = $builder;
    }

    /**
     * @param string $locale
     * @return ITranslator
     * @throws \Throwable
     */
    public function getTranslator(string $locale): ITranslator
    {
        $locale = strtolower($locale);
        if (!isset($this->translators[$locale])) {
            $this->translators[$locale] = $this->createTranslator($locale);
        }

        return $this->translators[$locale];
    }

    /**
     * @param string $locale
     * @return ITranslator
     * @throws \Throwable
     */
    protected function createTranslator(string $locale): ITranslator
    {
        if (!isset($this->catalogues[$locale])) {
            throw new TranslatorException("Language {$locale} requested, but corresponding catalogue missing.");
        }

        return new Translator($this->catalogues[$locale]->compile(), $this->diagnostics);
    }
}
