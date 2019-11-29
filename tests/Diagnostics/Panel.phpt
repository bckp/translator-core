<?php declare(strict_types=1);

namespace Bckp\Translator;

use Bckp\Translator\Diagnostics\Diagnostics;
use Bckp\Translator\Diagnostics\Panel;
use Tester\Assert;

require __DIR__ . '/../bootstrap.php';

$panel = new Diagnostics();
Assert::falsey($panel->getWarnings());
$panel->warning('bug 1');
Assert::equal(1, count($panel->getWarnings()));
Assert::same(['bug 1'], $panel->getWarnings());
$panel->warning('bug 2');
Assert::equal(2, count($panel->getWarnings()));
$panel->warning('bug 1');
Assert::equal(2, count($panel->getWarnings()));

Assert::falsey($panel->getUntranslated());
$panel->untranslated('bug 1');
Assert::equal(1, count($panel->getUntranslated()));
Assert::same(['bug 1'], $panel->getUntranslated());
$panel->untranslated('bug 2');
Assert::equal(2, count($panel->getUntranslated()));
Assert::same(['bug 1', 'bug 2'], $panel->getUntranslated());
$panel->untranslated('bug 1');
Assert::equal(2, count($panel->getUntranslated()));
Assert::same(['bug 1', 'bug 2'], $panel->getUntranslated());

$panel->setLocale('cs');
Assert::same('cs', $panel->getLocale());
