<?php

declare(strict_types=1);

require __DIR__ . '/bootstrap.php';
require __DIR__ . '/CountingSource.php';

use Bckp\Translator\CatalogueBuilder;
use Bckp\Translator\CatalogueStatus;
use Bckp\Translator\Diagnostics\Diagnostics;
use Bckp\Translator\Storage\MemoryStorage;
use Bckp\Translator\Tests\CountingSource;
use Tester\Assert;

$storage = new MemoryStorage();
$first = new CountingSource('same', 'First');
$last = new CountingSource('same', 'Last');
$builder = new CatalogueBuilder($storage, 'cs')->setCheckProbability(0)->addSource('first', $first)->addSource('last', $last);
Assert::same('Last', $builder->compile()->get('messages.hello'));
$reordered = new CatalogueBuilder($storage, 'cs')->setCheckProbability(0)->addSource('last', $last)->addSource('first', $first);
Assert::same('First', $reordered->compile()->get('messages.hello'));
Assert::same(2, $first->loadCalls);
$first->text = 'Independent namespace';
$independent = new CatalogueBuilder($storage, 'cs', namespace: 'another')->setCheckProbability(0)->addSource('first', $first)->addSource('last', $last);
$independent->compile();
Assert::same(3, $first->loadCalls);
$diagnostics = new Diagnostics();
$warm = new CatalogueBuilder($storage, 'cs', diagnostics: $diagnostics)->setCheckProbability(0)->addSource('first', $first)->addSource('last', $last);
Assert::same('Last', $warm->compile()->get('messages.hello'));
$diagnostics->eachCatalogue(static function (CatalogueStatus $status): void {
	Assert::false($status->checked);
	Assert::false($status->recompiled);
	Assert::same('same', $status->catalogue->versions->get('first'));
});
Assert::same(3, $first->loadCalls);
