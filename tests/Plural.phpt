<?php

declare(strict_types=1);

require __DIR__ . '/bootstrap.php';

use Bckp\Translator\Locale;
use Bckp\Translator\Plural;
use Bckp\Translator\PluralProvider;
use Bckp\Translator\PluralRule;
use Tester\Assert;

$provider = new PluralProvider();
Assert::same(PluralRule::Czech, $provider->getRule('CS_CZ'));
Assert::same(PluralRule::English, $provider->getRule('en-US'));
Assert::same(PluralRule::Invariant, $provider->getRule('ja'));
Assert::same(Plural::Zero, PluralRule::Czech->select(0));
Assert::same(Plural::One, PluralRule::Czech->select(1));
Assert::same(Plural::Few, PluralRule::Czech->select(4));
Assert::same(Plural::Other, PluralRule::Czech->select(5));
Assert::same(Plural::Other, PluralRule::Czech->select(-5));
Assert::same(Plural::One, PluralRule::English->select(1));
Assert::same(Plural::Other, PluralRule::Invariant->select(1));
Assert::same('cs-cz', Locale::normalize('CS_CZ'));
Assert::exception(static fn() => Locale::normalize('../invalid'), InvalidArgumentException::class);
