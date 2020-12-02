<?php declare(strict_types=1);

namespace Bckp\Translator;

use Bckp\Translator\Builder\Catalogue;
use Tester\Assert;

require __DIR__ . '/../bootstrap.php';
$plural = (new PluralProvider());

const LOCALE = 'dynamic';
const RESOURCE = 'test';

$catalogue = new Catalogue($plural, TEMP_DIR, LOCALE);
$catalogue->addDynamic(RESOURCE, function (array &$messages, string &$resource, string &$locale) {
    # Verify callback is called properly
    Assert::equal($locale, LOCALE);
    Assert::equal($resource, RESOURCE);
    Assert::true(is_array($messages));

    # Add string
    $messages['string'] = 'test';

    # Try modify resource and locale
    $locale = LOCALE . '2';
    $resource = RESOURCE . '3';
});

$catalogue->addCompileCallback(function (array &$messages, string $locale) {
    Assert::equal('test', $messages[RESOURCE . '.string']);
    Assert::equal(LOCALE, $locale);

    $messages[RESOURCE . '.string'] = 'test2';
});

$compiled = $catalogue->compile();
Assert::same('test2', $compiled->get('test.string'));
