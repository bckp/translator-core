<?php declare(strict_types=1);

namespace Bckp\Translator;

use Bckp\Translator\Builder\Catalogue;
use Bckp\Translator\Diagnostics\Diagnostics;
use Nette\Utils\Html;
use Tester\Assert;

require __DIR__ . '/../bootstrap.php';

// Prepare
$plural = new PluralProvider;
$panel = new Diagnostics;
$provider = new TranslatorProvider(['cs', 'en'], $panel);
$provider->addCatalogue('cs', (new Catalogue($plural, TEMP_DIR, 'cs'))->addFile('./translations/test.cs.neon'));
$provider->addCatalogue('en', (new Catalogue($plural, TEMP_DIR, 'en'))->addFile('./translations/test.en.neon'));
$provider->addCatalogue('hu', (new Catalogue($plural, TEMP_DIR, 'hu')));

// Test object
$string = new class {

	public function __toString() {
		return 'test.welcome';
	}
};
$nonstring = new class {

	public function get() {
		return 'ahoj';
	}
};

// cs translator
$translator = $provider->getTranslator('cs');
Assert::equal('Vítejte', $translator->translate('test.welcome'));
Assert::equal('Vítejte', $translator->translate(['test.welcome', 3]));
Assert::equal('Vítejte', $translator->translate('test.welcome', 3));
Assert::equal('Vítejte', $translator->translate($string));
Assert::equal('', $translator->translate(''));
Assert::equal('not.existing', $translator->translate('not.existing'));
Assert::equal('html', $translator->translate(Html::el()->setText('html')));
Assert::equal('test.blank', $translator->translate('test.blank'), 'Translation is empty');
Assert::equal('Expected string|array|object::__toString, but NULL given.', $translator->translate($nonstring));
Assert::equal('zapnuto', $translator->translate('test.options'));
Assert::equal('zapnuto', $translator->translate(['test.options', 7]));

// en translator
$translator = $provider->getTranslator('en');
Assert::equal('Welcome', $translator->translate('test.welcome'));
Assert::equal('', $translator->translate(''));
Assert::equal('not.existing', $translator->translate('not.existing'));

// hu translator
$translator = $provider->getTranslator('hu');
Assert::equal('test.welcome', $translator->translate('test.welcome'));
Assert::equal('', $translator->translate(''));
Assert::equal('not.existing', $translator->translate('not.existing'));

// Warning and error should be defined
Assert::truthy($panel->getUntranslated());
Assert::truthy($panel->getWarnings());

// Exception on non-exists catalogue
Assert::exception(function () use ($provider) {
	return $provider->getTranslator('jp');
}, TranslatorException::class);