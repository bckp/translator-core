<?php

declare(strict_types=1);

require __DIR__ . '/bootstrap.php';

use Tester\Assert;

$path = TEMP_DIR . '/shared';
$command = [PHP_BINARY];

if (defined('PHPDBG_VERSION')) {
	array_push($command, '-qrrb', '-S', 'cli');
}
array_push($command, '-d', 'extension_dir=' . ini_get('extension_dir'));
$configuration = php_ini_loaded_file();

if ($configuration !== false) {
	$command[] = '-c';
	$command[] = $configuration;
}
$command[] = __DIR__ . '/CacheProcess.php';
$command[] = $path;
$command[] = 'initial';
$valueIndex = count($command) - 1;
$process = proc_open($command, [1 => ['pipe', 'w'], 2 => ['pipe', 'w']], $pipes);
Assert::true(is_resource($process));
Assert::same('initial', stream_get_contents($pipes[1]));
Assert::same('', stream_get_contents($pipes[2]));
fclose($pipes[1]);
fclose($pipes[2]);
Assert::same(0, proc_close($process));

$writers = [];
for ($i = 0; $i < 8; ++$i) {
	$command[$valueIndex] = 'writer-' . $i;
	$process = proc_open($command, [1 => ['pipe', 'w'], 2 => ['pipe', 'w']], $pipes);
	Assert::true(is_resource($process));
	$writers[] = [$process, $pipes];
}
foreach ($writers as [$process, $pipes]) {
	Assert::match('writer-%d%', stream_get_contents($pipes[1]));
	Assert::same('', stream_get_contents($pipes[2]));
	fclose($pipes[1]);
	fclose($pipes[2]);
	Assert::same(0, proc_close($process));
}
$command[$valueIndex] = 'read';
$process = proc_open($command, [1 => ['pipe', 'w'], 2 => ['pipe', 'w']], $pipes);
Assert::true(is_resource($process));
Assert::match('writer-%d%', stream_get_contents($pipes[1]));
Assert::same('', stream_get_contents($pipes[2]));
fclose($pipes[1]);
fclose($pipes[2]);
Assert::same(0, proc_close($process));
Assert::same([], glob($path . '/.catalogue-*'));
