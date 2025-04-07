<?php

declare(strict_types=1);

namespace Bckp\Translator;

use Nette\Utils\Random;
use Tester\Environment;

use const TEMP_DIR;

require __DIR__ . '/../vendor/autoload.php';

define('TEMP_DIR', __DIR__ . '/../temp/' . Random::generate(10));
if (file_exists(TEMP_DIR)) {
    @unlink(TEMP_DIR);
}
if (!mkdir($concurrentDirectory = TEMP_DIR, 0775, true) && !is_dir($concurrentDirectory)) {
    throw new \RuntimeException(sprintf('Directory "%s" was not created', $concurrentDirectory));
}

Environment::setup();
@unlink(TEMP_DIR);
