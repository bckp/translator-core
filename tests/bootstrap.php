<?php

declare(strict_types=1);

require __DIR__ . '/../vendor/autoload.php';

define('TEMP_DIR', __DIR__ . '/../temp/' . bin2hex(random_bytes(8)));
mkdir(TEMP_DIR, 0o775, true);

Tester\Environment::setup();
