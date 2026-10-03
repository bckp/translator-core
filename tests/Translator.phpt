<?php

declare(strict_types=1);

require __DIR__ . '/bootstrap.php';

use Bckp\Translator\CatalogueBuilder;
use Bckp\Translator\Diagnostics\Diagnostics;
use Bckp\Translator\Exceptions\TranslatorException;
use Bckp\Translator\MessageCatalogue;
use Bckp\Translator\PluralMessage;
use Bckp\Translator\Sources\StaticSource;
use Bckp\Translator\Storage\MemoryStorage;
use Bckp\Translator\Translator;
use Tester\Assert;

$messages = (new MessageCatalogue())->add('hello', 'Hello')->add('0', 'Zero key')->add('zero', '0')->add('empty', '')
	->add('format', 'Hello %s')->add('normalize', '%value: %s')->add('reversed', '%2$s %1$s')
	->add('people', new PluralMessage(zero: 'Nobody', one: '%d person', few: '%d people', other: '%d people'))
	->add('options', new PluralMessage(zero: 'off', one: 'on'))->add('bad', '%s %s');
$storage = new MemoryStorage();
$builder = (new CatalogueBuilder($storage, 'cs'))->addSource('app', new StaticSource($messages, 'v1'));
$diagnostics = new Diagnostics();
$translator = new Translator($builder->compile(), $diagnostics);
Assert::same('Hello', $translator->translate('hello'));
Assert::same('Zero key', $translator->translate('0'));
Assert::same('0', $translator->translate('zero'));
Assert::same('', $translator->translate('empty'));
Assert::same('', $translator->translate(''));
Assert::same('Hello Ada', $translator->translate('format', 'Ada'));
Assert::same('%value: Ada', $translator->translate('normalize', 'Ada'));
Assert::same('second first', $translator->translate('reversed', 'first', 'second'));
Assert::same('Nobody', $translator->translate('people', 0));
Assert::same('1 person', $translator->translate('people', 1));
Assert::same('3 people', $translator->translate('people', 3));
Assert::same('5 people', $translator->translate('people', 5));
Assert::same('on', $translator->translate('options', 3));
Assert::same(1, $diagnostics->getWarnings()->count());
Assert::same('missing', $translator->translate('missing'));
$translator->translate('missing');
Assert::same(1, $diagnostics->getUntranslated()->count());
Assert::exception(static fn() => $translator->translate('bad', 'only one'), TranslatorException::class);
$translator->setNormalizeCallback(static fn(string $value): int => 1);
Assert::exception(static fn() => $translator->translate('format', 'Ada'), UnexpectedValueException::class);
$replacement = (new CatalogueBuilder($storage, 'cs', namespace: 'replacement'))
	->addSource('app', new StaticSource((new MessageCatalogue())->add('hello', 'Updated'), 'v1'))->compile();
$translator->replaceCatalogue($replacement);
Assert::same('Updated', $translator->translate('hello'));
$otherLocale = (new CatalogueBuilder($storage, 'en'))->compile();
Assert::exception(static fn() => $translator->replaceCatalogue($otherLocale), TranslatorException::class);
Assert::same('cs', $translator->getLocale());
