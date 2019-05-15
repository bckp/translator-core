<?php declare(strict_types=1);

namespace Bckp\Translator;

use Tester\Assert;

require __DIR__ . '/../bootstrap.php';

$provider = new PluralProvider;

Assert::type('callable', $provider->getPlural('cs'));
Assert::type('callable', $provider->getPlural('en'));
Assert::type('callable', $provider->getPlural('non-sense'));

$plural = $provider->getPlural('cs');
Assert::same(IPlural::ZERO, $plural(0));
Assert::same(IPlural::ONE, $plural(1));
Assert::same(IPlural::FEW, $plural(2));
Assert::same(IPlural::FEW, $plural(3));
Assert::same(IPlural::FEW, $plural(4));
Assert::same(IPlural::OTHER, $plural(5));

$plural = $provider->getPlural('en');
Assert::same(IPlural::ZERO, $plural(0));
Assert::same(IPlural::ONE, $plural(1));
Assert::same(IPlural::OTHER, $plural(2));
Assert::same(IPlural::OTHER, $plural(5));

$plural = $provider->getPlural('ja');
Assert::same(IPlural::ZERO, $plural(0));
Assert::same(IPlural::OTHER, $plural(5));
