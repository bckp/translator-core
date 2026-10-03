<?php

declare(strict_types=1);

require __DIR__ . '/bootstrap.php';

use Bckp\Translator\Neon\InvalidTranslationException;
use Bckp\Translator\Neon\NeonSource;
use Bckp\Translator\PluralMessage;
use Bckp\Translator\StringList;
use Tester\Assert;

$path = TEMP_DIR . '/locales';
mkdir($path);
$file = $path . '/messages.cs.neon';
file_put_contents($file, "hello: Hello\npeople:\n    one: '%d person'\n    other: '%d people'\n");
$source = new NeonSource($path);
$version = $source->getVersion('cs');
Assert::same('Hello', $source('cs')->get('messages.hello'));
Assert::type(PluralMessage::class, $source('cs')->get('messages.people'));
Assert::same($version, $source->getVersion('cs'));
$mtime = filemtime($file);
file_put_contents($file, "hello: Updated\n");
touch($file, $mtime);
Assert::notSame($version, $source->getVersion('cs'));
$version = $source->getVersion('cs');
file_put_contents($path . '/extra.cs.neon', "new: New\n");
Assert::notSame($version, $source->getVersion('cs'));
$version = $source->getVersion('cs');
unlink($path . '/extra.cs.neon');
Assert::notSame($version, $source->getVersion('cs'));
$version = $source->getVersion('cs');
file_put_contents($path . '/messages.en.neon', "hello: English\n");
Assert::same($version, $source->getVersion('cs'));
file_put_contents($path . '/messages.CS_CZ.neon', "hello: Regional\n");
Assert::same('Regional', $source('cs-CZ')->get('messages.hello'));
$overridePath = TEMP_DIR . '/overrides';
mkdir($overridePath);
file_put_contents($overridePath . '/messages.cs.neon', "hello: Override\n");
Assert::same('Override', (new NeonSource(new StringList($path, $overridePath)))('cs')->get('messages.hello'));
Assert::same('release-42', (new NeonSource('/does-not-exist', 'release-42'))->getVersion('cs'));
foreach (["'scalar'\n", "bad: 123\n", "bad:\n    unknown: value\n", "bad: [\n", "bad:\n    other: false\n"] as $invalid) {
	file_put_contents($file, $invalid);
	Assert::exception(static fn() => $source('cs'), InvalidTranslationException::class);
}
file_put_contents($file, '');
Assert::same(0, $source('cs')->count());
