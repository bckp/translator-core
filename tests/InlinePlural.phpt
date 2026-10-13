<?php

declare(strict_types=1);

require __DIR__ . '/bootstrap.php';

use Bckp\Translator\CatalogueDefinition;
use Bckp\Translator\MessageCatalogue;
use Bckp\Translator\PhpCache\PhpCatalogueStorage;
use Bckp\Translator\PluralMessage;
use Bckp\Translator\PluralRule;
use Bckp\Translator\SourceVersions;
use Tester\Assert;

$numbers = [PHP_INT_MIN, -5, -1, 0, 1, 2, 3, 4, 5, 100, PHP_INT_MAX];
$cases = [];
foreach (PluralRule::cases() as $rule) {
	$directory = TEMP_DIR . '/' . $rule->value;
	$storage = new PhpCatalogueStorage($directory);
	$storage->save('rule', new CatalogueDefinition(
		'cs',
		123,
		new SourceVersions(),
		new MessageCatalogue()
			->add('items', new PluralMessage(zero: 'zero', one: 'one', two: 'two', few: 'few', many: 'many', other: 'other'))
			->add('options', new PluralMessage(one: 'fallback')),
		$rule,
	));
	$expected = [];
	foreach ($numbers as $number) {
		$expected[$number] = $rule->select($number)->value;
	}
	$files = glob($directory . '/*.php');
	Assert::same(1, count($files));
	$cases[] = ['file' => $files[0], 'expected' => $expected];
}

$program = <<<'PHP'
	<?php
	declare(strict_types=1);
	require __AUTOLOAD__;
	require __PROVIDER__;
	foreach (__CASES__ as $case) {
		$catalogue = require $case['file'];
		foreach ($case['expected'] as $number => $expected) {
			if ($catalogue->plural($number)->value !== $expected || $catalogue->get('items', $number) !== $expected) {
				throw new RuntimeException('Compiled rule differs from the provider at compilation.');
			}
		}
		if ($catalogue->get('items', ' 3 ') !== $case['expected'][3]
			|| $catalogue->get('items', 3.9) !== $case['expected'][3]
			|| $catalogue->get('items') !== 'other'
			|| $catalogue->get('options', 3) !== 'fallback'
			|| $catalogue->missingPluralForm('options', 3)?->value !== $case['expected'][3]
		) {
			throw new RuntimeException('Compiled plural lookup or diagnostics differs.');
		}
	}
	echo "OK\n";
	PHP;
$script = TEMP_DIR . '/standalone.php';
file_put_contents($script, strtr($program, [
	'__AUTOLOAD__' => var_export(__DIR__ . '/../vendor/autoload.php', true),
	'__PROVIDER__' => var_export(__DIR__ . '/UnavailablePluralProvider.php', true),
	'__CASES__' => var_export($cases, true),
]));
$command = function_exists('phpdbg_start_oplog') ? [PHP_BINARY, '-qrr', $script] : [PHP_BINARY, $script];
$process = proc_open($command, [1 => ['pipe', 'w'], 2 => ['pipe', 'w']], $pipes);
Assert::notSame(false, $process);
$output = stream_get_contents($pipes[1]);
$errors = stream_get_contents($pipes[2]);
fclose($pipes[1]);
fclose($pipes[2]);
Assert::same(0, proc_close($process), $errors);
Assert::same("OK\n", $output);

$directory = TEMP_DIR . '/previous-format';
mkdir($directory);
$marker = $directory . '/executed';
file_put_contents($directory . '/' . hash('sha256', '5:rule') . '.php', '<?php file_put_contents(' . var_export($marker, true) . ', "old"); return null;');
Assert::null(new PhpCatalogueStorage($directory)->load('rule'));
Assert::false(is_file($marker));
