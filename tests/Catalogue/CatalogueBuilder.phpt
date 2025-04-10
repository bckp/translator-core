<?php declare(strict_types=1);

namespace Bckp\Translator;

use Bckp\Translator\Exceptions\BuilderException;
use Bckp\Translator\Exceptions\FileInvalidException;
use Bckp\Translator\Exceptions\PathInvalidException;
use Tester\Assert;
use Tester\Environment;

require __DIR__ . '/../bootstrap.php';

$plural = (new PluralProvider());
Assert::exception(static function () use ($plural) {
    new CatalogueBuilder($plural, '/no-exists', 'x1');
}, PathInvalidException::class);
@unlink(TEMP_DIR . '/x1Catalogue.php');
Assert::exception(static function () use ($plural) {
    $catalogue = new CatalogueBuilder($plural, TEMP_DIR, 'x2');
    $catalogue->addFile('not-exists');
    $catalogue->compile();
}, PathInvalidException::class);
@unlink(TEMP_DIR . '/x2Catalogue.php');
Assert::exception(static function () use ($plural) {
    $catalogue = new CatalogueBuilder($plural, TEMP_DIR, 'x3');
    $catalogue->compile(4);
}, BuilderException::class);
@unlink(TEMP_DIR . '/x3Catalogue.php');
Assert::exception(static function () use ($plural) {
    $catalogue = new CatalogueBuilder($plural, TEMP_DIR, 'x4');
    $catalogue->addFile('./translations/broken.xx.neon');
    $catalogue->compile(2);
}, FileInvalidException::class);
@unlink(TEMP_DIR . '/x4Catalogue.php');
Assert::exception(static function () use ($plural) {
    $catalogue = new CatalogueBuilder($plural, TEMP_DIR, 'x5');
    $catalogue->addFile('./translations/string.xx.neon');
    $catalogue->compile(2);
}, FileInvalidException::class);
@unlink(TEMP_DIR . '/x5Catalogue.php');
Assert::exception(static function () use ($plural) {
    @unlink(TEMP_DIR . '/x6Catalogue.php');
    file_put_contents(TEMP_DIR . '/x6Catalogue.php', '<?php
return new Class{
	public $build = 123456;

	public function buildTime(){
		return $this->build;
	}
};
');
    $catalogue = new CatalogueBuilder($plural, TEMP_DIR, 'x6');
    $catalogue->compile(3);
}, BuilderException::class);
@unlink(TEMP_DIR . '/x7Catalogue.php');

@unlink(TEMP_DIR . '/x7Catalogue.php');
file_put_contents(TEMP_DIR . '/x7Catalogue.php', '<?php
return new Class{
	public $build = 123456;

	public function buildTime(){
		return $this->build;
	}
};
');
$catalogue = new CatalogueBuilder($plural, TEMP_DIR, 'x7');
$catalogue->addFile('./translations/test.cs.neon');
$compiled = $catalogue->compile(2);
Assert::type(Catalogue::class, $compiled);
Assert::type('string', $compiled->get('test.welcome'));
@unlink(TEMP_DIR . '/x7Catalogue.php');

// Rebuild 2
$catalogue = new CatalogueBuilder($plural, TEMP_DIR, 'x8');
$catalogue->addFile('./translations/test.cs.neon');
file_put_contents(TEMP_DIR . '/x8Catalogue.php', '<?php');
$compiled = $catalogue->compile(2);
Assert::same('x8', $compiled->locale());
Assert::same('x8', $catalogue->getLocale());
Assert::type(Catalogue::class, $compiled);
@unlink(TEMP_DIR . '/x8Catalogue.php');

$catalogue = new CatalogueBuilder($plural, TEMP_DIR, 'CS');
$catalogue->addFile('./translations/test.cs.neon');
$catalogue->addFile('./translations/blank.cs.neon');
Assert::same('cs', $catalogue->getLocale());
Assert::same('cs', $catalogue->compile()->locale());

$compiled = $catalogue->compile();
Assert::type(Catalogue::class, $compiled);
Assert::true(filemtime('./translations/test.cs.neon') < $compiled->build());
Assert::true($compiled->has('test.welcome'));
Assert::false($compiled->has('not-exists'));

// cs catalogue
Assert::type('string', $compiled->get('test.welcome'));
Assert::type('string', $compiled->get('test.not-exists'));
Assert::type('array', $compiled->get('test.plural'));
Assert::equal('Vítejte', $compiled->get('test.welcome'));
Assert::equal([
    'zero' => 'žádný člověk',
    'one' => 'jeden člověk',
    'few' => '%d lidé',
    'other' => '%d lidí',
], $compiled->get('test.plural'));

$catalogue = new CatalogueBuilder($plural, TEMP_DIR, 'EN');
$catalogue->addFile('./translations/test.en.neon');
Assert::same('en', $catalogue->getLocale());

$compiled = $catalogue->compile();
Assert::type(Catalogue::class, $compiled);
Assert::true(filemtime('./translations/test.en.neon') < $compiled->build());
Assert::same('en', $compiled->locale());

// en catalogue
Assert::type('string', $compiled->get('test.welcome'));
Assert::type('string', $compiled->get('test.not-exists'));
Assert::type('array', $compiled->get('test.plural'));
Assert::equal('Welcome', $compiled->get('test.welcome'));
Assert::equal([
    'zero' => 'no person',
    'one' => 'one person',
    'other' => '%d persons',
], $compiled->get('test.plural'));

// Check for update on debug mode
$time = time() - 15;
file_put_contents(TEMP_DIR . '/x9Catalogue.php', file_get_contents('assets/x9catalogue'));
touch(TEMP_DIR . '/x9Catalogue.php', $time);
if (filemtime(TEMP_DIR . '/x9Catalogue.php') === $time) {
    touch('./translations/blank.cs.neon');
    $catalogue = new CatalogueBuilder($plural, TEMP_DIR, 'x9');
    $catalogue->setDebugMode(true);
    $catalogue->addFile('./translations/test.cs.neon');
    $catalogue->addFile('./translations/blank.cs.neon');
    $compiled = $catalogue->compile();

    Assert::true($compiled->build() > $time);
} else {
    Environment::skip('Skipped test touch and debug');
}
@unlink(TEMP_DIR . '/x9Catalogue.php');
