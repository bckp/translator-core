<?php declare(strict_types=1);

namespace Bckp\Translator;

use Bckp\Translator\Builder\Catalogue;
use Tester\Assert;

require __DIR__ . '/../bootstrap.php';
$plural = (new PluralProvider());

const LOCALE = 'dynamic';
const RESOURCE = 'test';

$catalogue = new Catalogue($plural, TEMP_DIR, LOCALE);
$catalogue->addDynamic(RESOURCE, function (array &$messages) {
    $messages['string'] = 'test';
});

$compiled = $catalogue->compile();
$buildTime = $compiled->buildTime();
sleep(2);

$compiled = $catalogue->rebuild();
Assert::notEqual($buildTime, $compiled->buildTime());
