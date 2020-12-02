<?php declare(strict_types=1);

namespace Bckp\Translator;

use Tester\Assert;

require __DIR__ . '/../bootstrap.php';

$provider = new PluralProvider();

Assert::type('callable', $provider->getPlural('cs'));
Assert::type('callable', $provider->getPlural('en'));
Assert::type('callable', $provider->getPlural('non-sense'));

# Czech
$plural = $provider->getPlural('cs');
Assert::same('csPlural', $plural[1]);
Assert::same(PluralProvider::csPlural(0), $plural(0));
Assert::same(IPlural::ZERO, $plural(0));
Assert::same(PluralProvider::csPlural(1), $plural(1));
Assert::same(IPlural::ONE, $plural(1));
Assert::same(PluralProvider::csPlural(2), $plural(2));
Assert::same(IPlural::FEW, $plural(2));
Assert::same(PluralProvider::csPlural(3), $plural(3));
Assert::same(IPlural::FEW, $plural(3));
Assert::same(PluralProvider::csPlural(4), $plural(4));
Assert::same(IPlural::FEW, $plural(4));
Assert::same(PluralProvider::csPlural(5), $plural(5));
Assert::same(IPlural::OTHER, $plural(5));
Assert::same(PluralProvider::csPlural(-5), $plural(-5));
Assert::same(IPlural::OTHER, $plural(-5));

# English
$plural = $provider->getPlural('en');
Assert::same('enPlural', $plural[1]);
Assert::same(PluralProvider::enPlural(0), $plural(0));
Assert::same(IPlural::ZERO, $plural(0));
Assert::same(PluralProvider::enPlural(1), $plural(1));
Assert::same(IPlural::ONE, $plural(1));
Assert::same(PluralProvider::enPlural(2), $plural(2));
Assert::same(IPlural::OTHER, $plural(2));
Assert::same(PluralProvider::enPlural(5), $plural(5));
Assert::same(IPlural::OTHER, $plural(5));
Assert::same(PluralProvider::enPlural(-5), $plural(-5));
Assert::same(IPlural::OTHER, $plural(-5));

# Zero plural
foreach(['id','ja','ka','ko','lo','ms','my','th','vi','zh'] as $lang) {
    $plural = $provider->getPlural($lang);
    Assert::type('callable', $plural);
    Assert::same('zeroPlural', $plural[1]);
    Assert::same(PluralProvider::zeroPlural(0), $plural(0));
    Assert::same(IPlural::ZERO, $plural(0));
    Assert::same(PluralProvider::zeroPlural(5), $plural(5));
    Assert::same(IPlural::OTHER, $plural(5));
    Assert::same(PluralProvider::zeroPlural(-5), $plural(-5));
    Assert::same(IPlural::OTHER, $plural(-5));
}

# csPlural
Assert::same(IPlural::ZERO, PluralProvider::csPlural(0));
Assert::same(IPlural::ONE, PluralProvider::csPlural(1));
Assert::same(IPlural::FEW, PluralProvider::csPlural(2));
Assert::same(IPlural::OTHER, PluralProvider::csPlural(5));

# enPlural
Assert::same(IPlural::ZERO, PluralProvider::enPlural(0));
Assert::same(IPlural::ONE, PluralProvider::enPlural(1));
Assert::same(IPlural::OTHER, PluralProvider::enPlural(2));
Assert::same(IPlural::OTHER, PluralProvider::enPlural(5));

# zeroPlural
Assert::same(IPlural::ZERO, PluralProvider::zeroPlural(0));
Assert::same(IPlural::OTHER, PluralProvider::zeroPlural(1));
Assert::same(IPlural::OTHER, PluralProvider::zeroPlural(5));
Assert::same(IPlural::OTHER, PluralProvider::zeroPlural(-1));
