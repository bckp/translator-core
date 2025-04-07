<?php declare(strict_types=1);

namespace Bckp\Translator;

use Bckp\Translator\Diagnostics\Diagnostics;
use Bckp\Translator\Exceptions\TranslatorException;
use Nette\Utils\Html;
use Stringable;
use Tester\Assert;

require __DIR__ . '/../bootstrap.php';

// Prepare
$plural = new PluralProvider();
$panel = new Diagnostics();
$provider = new TranslatorProvider(['cs', 'en'], $panel);
$provider->addCatalogue('cs', (new CatalogueBuilder($plural, TEMP_DIR, 'cs'))->addFile('./translations/test.cs.neon'));
$provider->addCatalogue('en', (new CatalogueBuilder($plural, TEMP_DIR, 'en'))->addFile('./translations/test.en.neon'));
$provider->addCatalogue('hu', (new CatalogueBuilder($plural, TEMP_DIR, 'hu')));

// Test object
$string = new class implements Stringable {
    public function __toString(): string
    {
        return 'test.welcome';
    }
};
$nonString = new class {
    public function get()
    {
        return 'ahoj';
    }
};

// cs translator
$translator = $provider->getTranslator('cs');
Assert::equal('Vítejte', $translator->translate('test.welcome'));
Assert::equal('Vítejte', $translator->translate('test.welcome', 3));
Assert::equal('Vítejte', $translator->translate('test.welcome', 3));
Assert::equal('Vítejte', $translator->translate($string));
Assert::equal('', $translator->translate(''));
Assert::equal('not.existing', $translator->translate('not.existing'));
Assert::equal('html', $translator->translate(Html::el()->setText('html')));
Assert::equal('test.blank', $translator->translate('test.blank'), 'Translation is empty');
Assert::equal('zapnuto', $translator->translate('test.options'));
Assert::equal('zapnuto', $translator->translate('test.options', 7));
Assert::equal('5 lidí', $translator->translate('test.plural', 5, 5));

Assert::equal('1 2 3 4 5', $translator->translate('test.numbers', 1,2,3,4,5));
Assert::equal('5 4 3 2 1', $translator->translate('test.numbersReverse', 1,2,3,4,5));

Assert::equal('1 + 1 = 2', $translator->translate('test.justSecond', 5, 1));

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
Assert::exception(static function () use ($provider) {
    return $provider->getTranslator('jp');
}, TranslatorException::class);

// Normalize check
$translator = $provider->getTranslator('cs');

$callbackUsed = false;
$translator->setNormalizeCallback(function (string $string) use (&$callbackUsed) {
    $callbackUsed = true;
    return str_replace('%value', '%%value', $string);
});
Assert::equal('Hodnota prvku %value ma byt test.', $translator->translate('test.normalize', parameters: 'test'));
Assert::true($callbackUsed, 'Callback should be used.');

$callbackUsed = false;
$translator->setNormalizeCallback(function (string $string) use (&$callbackUsed) {
    $callbackUsed = true;
    return str_replace('%value', '%%value', $string);
});
Assert::equal('Hodnota prvku %value.', $translator->translate('test.normalize2'));
Assert::false($callbackUsed, 'Callback shouldn`t be used.');
