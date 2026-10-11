<?php

declare(strict_types=1);

require __DIR__ . '/../vendor/autoload.php';

$status = function_exists('opcache_get_status') ? opcache_get_status(false) : false;

if (!($status['opcache_enabled'] ?? false)) {
	throw new RuntimeException('OPcache must be active for this benchmark.');
}

if ($status['jit']['on'] ?? false) {
	throw new RuntimeException('JIT must be disabled for this benchmark.');
}

if (extension_loaded('xdebug') && ini_get('xdebug.mode') !== 'off') {
	throw new RuntimeException('Xdebug must be disabled for this benchmark.');
}
