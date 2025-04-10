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
$builder = new CatalogueBuilder($plural, TEMP_DIR, 'en');
$builder->addFile('./translations/test.en.neon');

// cs translator
$translator = new Translator($builder->compile(), $panel);
Assert::equal('Welcome', $translator->translate('test.welcome'));
Assert::equal('', $translator->translate(''));
Assert::equal('not.existing', $translator->translate('not.existing'));
