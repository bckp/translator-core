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

$compiled = $catalogue->compile();

# Check translation
Assert::same('test', $compiled->get('test.string'));

# Check locale is correct and can`t be changed
Assert::same($compiled->locale(), LOCALE);

# Verify messages is not empty
$catalogue->addDynamic('test2', function (array &$messages, string $resource, string &$locale) {
    # Locale and Resource should not be modified
    Assert::equal($locale, LOCALE);
    Assert::equal($resource, RESOURCE);

    # Messages should be filled
    Assert::truthy($messages, 'Messages should not be empty, as we add some items in it');
});
