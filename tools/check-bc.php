<?php

declare(strict_types=1);

function output(string ...$command): string
{
	$process = proc_open($command, [STDIN, ['pipe', 'w'], STDERR], $pipes);

	if (!is_resource($process)) {
		throw new RuntimeException('Cannot start command.');
	}
	$result = stream_get_contents($pipes[1]);
	fclose($pipes[1]);
	$status = proc_close($process);

	if ($result === false || $status !== 0) {
		throw new RuntimeException('Command failed: ' . implode(' ', $command));
	}

	return trim($result);
}

$major = filter_var($argv[1] ?? null, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);

if (!is_int($major)) {
	throw new InvalidArgumentException('Usage: php tools/check-bc.php <major> [from-ref]');
}
$baseline = $argv[2] ?? null;

if ($baseline === null) {
	foreach (explode("\n", output('git', 'tag', '--list')) as $tag) {
		if (preg_match('/^v?' . $major . '\.[0-9]+\.[0-9]+$/D', $tag) !== 1) {
			continue;
		}

		if ($baseline === null || version_compare(ltrim($tag, 'v'), ltrim($baseline, 'v'), '>')) {
			$baseline = $tag;
		}
	}
}

if ($baseline === null) {
	echo "No stable $major.x release tag: API compatibility checking starts after the first release.\n";
	exit(0);
}
$root = dirname(__DIR__);
$binary = $root . '/tools/bc/vendor/bin/roave-backward-compatibility-check';

if (!is_file($binary)) {
	throw new RuntimeException('Run composer bc-install before checking API compatibility.');
}
$temporary = $root . '/temp/bc-' . bin2hex(random_bytes(8));

if (!mkdir($temporary, 0o775, true)) {
	throw new RuntimeException('Cannot create the API comparison directory.');
}

foreach (['translator-core', 'translator-neon'] as $dependency) {
	$source = dirname($root) . '/' . $dependency;

	if (is_dir($source) && !symlink($source, $temporary . '/' . $dependency)) {
		throw new RuntimeException("Cannot link dependency '$dependency'.");
	}
}
$environment = getenv();
$environment['TMPDIR'] = $temporary;
$format = getenv('GITHUB_ACTIONS') === 'true' ? 'github-actions' : 'console';

echo "Comparing API from $baseline to HEAD.\n";
$process = proc_open(
	[PHP_BINARY, $binary, '--from=' . $baseline, '--to=HEAD', '--format=' . $format],
	[STDIN, STDOUT, STDERR],
	$pipes,
	$root,
	$environment,
);

if (!is_resource($process)) {
	throw new RuntimeException('Cannot start the API compatibility checker.');
}
exit(proc_close($process));
