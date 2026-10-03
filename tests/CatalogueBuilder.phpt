<?php

declare(strict_types=1);

require __DIR__ . '/bootstrap.php';
require __DIR__ . '/CountingSource.php';

use Bckp\Translator\CatalogueBuilder;
use Bckp\Translator\Storage\MemoryStorage;
use Bckp\Translator\Tests\CountingSource;
use Tester\Assert;

$storage = new MemoryStorage();
$source = new CountingSource();
$request = static fn(): CatalogueBuilder => (new CatalogueBuilder($storage, 'CS'))->addSource('application', $source);
$first = $request()->setCheckProbability(0);
$catalogue = $first->compile();
Assert::same('cs', $catalogue->locale);
Assert::same('Hello', $catalogue->get('messages.hello'));
Assert::same(1, $source->versionCalls);
Assert::same(1, $source->loadCalls);
Assert::same($catalogue, $first->compile());
Assert::same(1, $source->versionCalls);
$source->version = 'v2';
$source->text = 'New';
Assert::same('Hello', $request()->setCheckProbability(0)->compile()->get('messages.hello'));
Assert::same(1, $source->versionCalls);
Assert::same(1, $source->loadCalls);
Assert::same('New', $request()->setCheckProbability(1)->compile()->get('messages.hello'));
Assert::same(2, $source->versionCalls);
Assert::same(2, $source->loadCalls);
$request()->setCheckProbability(1)->compile();
Assert::same(3, $source->versionCalls);
Assert::same(2, $source->loadCalls);
$source->version = 'v0';
$source->text = 'Rollback';
$debug = $request()->setCheckProbability(0)->setDebugMode(true);
Assert::same('Rollback', $debug->compile()->get('messages.hello'));
Assert::same(4, $source->versionCalls);
Assert::same(3, $source->loadCalls);
$debug->compile();
Assert::same(4, $source->versionCalls);
$source->text = 'Forced with same version';
Assert::same('Forced with same version', $debug->forceRecompile()->get('messages.hello'));
Assert::same(5, $source->versionCalls);
Assert::same(4, $source->loadCalls);
$source->fail = true;
Assert::exception(static fn() => $debug->forceRecompile(), RuntimeException::class, 'Source failed.');
Assert::same('Forced with same version', $debug->compile()->get('messages.hello'));
Assert::same('Forced with same version', $request()->setCheckProbability(0)->compile()->get('messages.hello'));
$source->fail = false;
foreach ([-0.1, 1.1, INF, NAN] as $probability) {
	Assert::exception(static fn() => $request()->setCheckProbability($probability), InvalidArgumentException::class);
}
Assert::exception(static fn() => $request()->addSource('application', $source), InvalidArgumentException::class);
Assert::exception(static fn() => $request()->addSource('', $source), InvalidArgumentException::class);
Assert::exception(static fn() => $debug->addSource('later', $source), LogicException::class);
mt_srand(42);
$before = $source->versionCalls;
for ($i = 0; $i < 400; ++$i) {
	$request()->setCheckProbability(0.5)->compile();
}
$checks = $source->versionCalls - $before;
Assert::true($checks > 150 && $checks < 250);
