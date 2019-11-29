<?php declare(strict_types=1);

namespace Bckp\Translator;

use Tester\Environment;
use function lcg_value;
use const TEMP_DIR;

require __DIR__ . '/../vendor/autoload.php';

define('TEMP_DIR', __DIR__ . '/../temp/' . (string)lcg_value());
if (file_exists(TEMP_DIR)) {
    @unlink(TEMP_DIR);
}
mkdir(TEMP_DIR, 0775, true);

Environment::setup();
@unlink(TEMP_DIR);
