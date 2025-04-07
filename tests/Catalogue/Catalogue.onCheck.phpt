<?php declare(strict_types=1);

namespace Bckp\Translator;

use Bckp\Translator\Exceptions\BuilderException;
use Tester\Assert;

require __DIR__ . '/../bootstrap.php';
$plural = (new PluralProvider());

const LOCALE = 'dynamic';
const RESOURCE = 'test';

$catalogue = new CatalogueBuilder($plural, TEMP_DIR, LOCALE);
$catalogue->addDynamic(RESOURCE, function (array &$messages) {
    $messages['string'] = 'test';
});
$catalogue->addCheckCallback(function () {
    throw new BuilderException('Rebuild required');
});

Assert::noError(static function () use ($catalogue) {
    $catalogue->compile();
});

Assert::exception(static function () use ($catalogue) {
    $catalogue->setDebugMode(true);
    $catalogue->compile();
}, BuilderException::class);

Assert::exception(static function () use ($catalogue) {
    $catalogue->setDebugMode(true);
    $catalogue->compile(2);
}, BuilderException::class);

Assert::exception(static function () use ($catalogue) {
    $catalogue->setDebugMode(true);
    $catalogue->compile(3);
}, BuilderException::class);
