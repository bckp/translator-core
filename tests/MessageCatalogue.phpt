<?php

declare(strict_types=1);

require __DIR__ . '/bootstrap.php';

use Bckp\Translator\MessageCatalogue;
use Bckp\Translator\Plural;
use Bckp\Translator\PluralMessage;
use Bckp\Translator\SourceVersion;
use Bckp\Translator\SourceVersions;
use Bckp\Translator\Sources\StaticSource;
use Bckp\Translator\Sources\CallbackSource;
use Tester\Assert;

$messages = new MessageCatalogue()->add('hello', 'Hello')->add('zero', '0')->add('empty', '');
Assert::same('0', $messages->get('zero'));
Assert::same('', $messages->get('empty'));
Assert::null($messages->get('missing'));
Assert::false($messages->has('missing'));
Assert::exception(static fn() => $messages->add('bad', 123), TypeError::class);
$plural = new PluralMessage(one: 'One', other: '%d people');
$messages->add('people', $plural);
Assert::same('One', $plural->select(Plural::One));
Assert::null($plural->select(Plural::Few));
Assert::same('%d people', $plural->fallback);
Assert::exception(static fn() => new PluralMessage(), InvalidArgumentException::class);
$messages->merge(new MessageCatalogue()->add('hello', 'Overridden'));
Assert::same('Overridden', $messages->get('hello'));
Assert::same(4, $messages->count());
$source = new StaticSource($messages, 'fixed');
$messages->add('hello', 'Changed outside');
$source('cs')->add('hello', 'Changed returned value');
Assert::same('Overridden', $source('cs')->get('hello'));
Assert::same('fixed', $source->getVersion('cs'));
$callback = new CallbackSource(
	static fn(string $locale): MessageCatalogue => new MessageCatalogue()->add('locale', $locale),
	static fn(string $locale): string => 'revision-' . $locale,
);
Assert::same('revision-cs', $callback->getVersion('cs'));
Assert::same('cs', $callback('cs')->get('locale'));
Assert::exception(static fn() => (new CallbackSource(static fn(): array => [], 'v1'))('cs'), UnexpectedValueException::class);
Assert::exception(static fn() => new CallbackSource(static fn(): MessageCatalogue => new MessageCatalogue(), static fn(): int => 1)->getVersion('cs'), UnexpectedValueException::class);
$a = new SourceVersions(new SourceVersion('a', ''), new SourceVersion('b', '0'));
$b = new SourceVersions(new SourceVersion('a', ''), new SourceVersion('b', '0'));
Assert::true($a->equals($b));
Assert::same('', $a->get('a'));
Assert::same('0', $a->get('b'));
Assert::false($a->equals(new SourceVersions(new SourceVersion('b', '0'), new SourceVersion('a', ''))));
Assert::exception(static fn() => new SourceVersions(new SourceVersion('a', '1'), new SourceVersion('a', '2')), InvalidArgumentException::class);
