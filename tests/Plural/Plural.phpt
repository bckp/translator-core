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
Assert::same(Plural::Zero, $plural(0));
Assert::same(PluralProvider::csPlural(1), $plural(1));
Assert::same(Plural::One, $plural(1));
Assert::same(PluralProvider::csPlural(2), $plural(2));
Assert::same(Plural::Few, $plural(2));
Assert::same(PluralProvider::csPlural(3), $plural(3));
Assert::same(Plural::Few, $plural(3));
Assert::same(PluralProvider::csPlural(4), $plural(4));
Assert::same(Plural::Few, $plural(4));
Assert::same(PluralProvider::csPlural(5), $plural(5));
Assert::same(Plural::Other, $plural(5));
Assert::same(PluralProvider::csPlural(-5), $plural(-5));
Assert::same(Plural::Other, $plural(-5));

# English
$plural = $provider->getPlural('en');
Assert::same('enPlural', $plural[1]);
Assert::same(PluralProvider::enPlural(0), $plural(0));
Assert::same(Plural::Zero, $plural(0));
Assert::same(PluralProvider::enPlural(1), $plural(1));
Assert::same(Plural::One, $plural(1));
Assert::same(PluralProvider::enPlural(2), $plural(2));
Assert::same(Plural::Other, $plural(2));
Assert::same(PluralProvider::enPlural(5), $plural(5));
Assert::same(Plural::Other, $plural(5));
Assert::same(PluralProvider::enPlural(-5), $plural(-5));
Assert::same(Plural::Other, $plural(-5));

# Zero plural
foreach(['id','ja','ka','ko','lo','ms','my','th','vi','zh'] as $lang) {
    $plural = $provider->getPlural($lang);
    Assert::type('callable', $plural);
    Assert::same('zeroPlural', $plural[1]);
    Assert::same(PluralProvider::zeroPlural(0), $plural(0));
    Assert::same(Plural::Zero, $plural(0));
    Assert::same(PluralProvider::zeroPlural(5), $plural(5));
    Assert::same(Plural::Other, $plural(5));
    Assert::same(PluralProvider::zeroPlural(-5), $plural(-5));
    Assert::same(Plural::Other, $plural(-5));
}

# csPlural
Assert::same(Plural::Zero, PluralProvider::csPlural(0));
Assert::same(Plural::One, PluralProvider::csPlural(1));
Assert::same(Plural::Few, PluralProvider::csPlural(2));
Assert::same(Plural::Other, PluralProvider::csPlural(5));

# enPlural
Assert::same(Plural::Zero, PluralProvider::enPlural(0));
Assert::same(Plural::One, PluralProvider::enPlural(1));
Assert::same(Plural::Other, PluralProvider::enPlural(2));
Assert::same(Plural::Other, PluralProvider::enPlural(5));

# zeroPlural
Assert::same(Plural::Zero, PluralProvider::zeroPlural(0));
Assert::same(Plural::Other, PluralProvider::zeroPlural(1));
Assert::same(Plural::Other, PluralProvider::zeroPlural(5));
Assert::same(Plural::Other, PluralProvider::zeroPlural(-1));
