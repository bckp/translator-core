<?php declare(strict_types=1);

namespace Bckp\Translator;

use Tester\Assert;

require __DIR__ . '/../bootstrap.php';
$plural = (new PluralProvider());

const LOCALE = 'dynamic';
const RESOURCE = 'test';

$catalogue = new CatalogueBuilder($plural, TEMP_DIR, LOCALE);
$catalogue->addDynamic(RESOURCE, function (array &$messages) {
    $messages['string'] = 'test';
});

$compiled = $catalogue->compile();
$buildTime = $compiled->build();
sleep(2);

$compiled = $catalogue->rebuild();
Assert::notEqual($buildTime, $compiled->build());
