<?php declare(strict_types=1);
/**
 * BCKP Translator
 * (c) Radovan Kepák
 *
 * For the full copyright and license information, please view the file license.md that was distributed with this source code.
 *
 * @author Radovan Kepak <radovan@kepak.eu>
 *  --------------------------------------------------------------------------
 */

namespace Bckp\Translator\Builder;

use Bckp\Translator\BuilderException;
use Bckp\Translator\FileInvalidException;
use Bckp\Translator\ICatalogue;
use Bckp\Translator\PathInvalidException;
use Bckp\Translator\PluralProvider;
use Nette\Neon\Neon;
use Nette\PhpGenerator\Method;
use Nette\PhpGenerator\PhpFile;
use function file_exists;
use function file_get_contents;
use function file_put_contents;
use function strtolower;
use Throwable;
use function unlink;

/**
 * Class Catalogue
 *
 * @package Bckp\Translator\Builder
 */
class Catalogue {

	/**
	 * @var bool
	 */
	private $debug;

	/**
	 * @var array
	 */
	private $collection = [];

	/**
	 * @var PluralProvider
	 */
	private $plural;

	/**
	 * @var string
	 */
	private $locale;

	/**
	 * @var string
	 */
	private $path;

	/**
	 * @var bool
	 */
	private $loaded = false;

	/**
	 * @var ICatalogue|null
	 */
	private $catalogue;

	/**
	 * Catalogue constructor.
	 *
	 * @param PluralProvider $plural
	 * @param string $path
	 * @param string $locale
	 */
	public function __construct(PluralProvider $plural, string $path, string $locale) {
		if (!is_writable($path))
			throw new PathInvalidException("Path '{$path}' is not writable.");

		$this->path = $path;
		$this->plural = $plural;
		$this->locale = strtolower($locale);
	}

	/**
	 * @return string
	 */
	public function getLocale(): string {
		return $this->locale;
	}

	/**
	 * Compile catalogue (or load from cache if exists)
	 *
	 * @param int $rebuild
	 * @return ICatalogue
	 * @throws Throwable
	 */
	public function compile(int $rebuild = 0): ICatalogue {
		// Exception on to many rebuild try
		if ($rebuild > 3)
			throw new BuilderException('Failed to build language file');

		// Check for file exist, create if no
		$filename = $this->path . '/' . $this->getName() . '.php';
		if (!file_exists($filename)) {
			file_put_contents($filename, $this->compileCode());
		}

		// Link file and rebuild if error
		try {
			if ($this->debug) {
				$cacheTime = (int) filemtime($filename);
				foreach ($this->collection as $file) {
					$file = new \SplFileInfo($file);
					$fileTime = $file->getMTime();

					if($fileTime > $cacheTime || ($this->catalogue && $fileTime > $this->catalogue->buildTime()))
						throw new BuilderException('Rebuild required');
				}
			}

			$this->link($filename);

			if (!$this->catalogue instanceof ICatalogue)
				throw new BuilderException('Catalogue is not implementing ICatalogue');
			return $this->catalogue;
		} catch (Throwable $e) {
			$this->unlink($filename);
			return $this->compile(++$rebuild);
		}
	}

	/**
	 * @return string
	 */
	protected function getName(): string {
		return $this->locale . 'Catalogue';
	}

	/**
	 * Compile cache code
	 *
	 * @return string
	 */
	protected function compileCode(): string {
		// Load messages, then generate code
		$messages = $this->getMessages();

		do {
			$className = $this->getName() . uniqid();
		}while(class_exists($className));

		// File
		$file = new PhpFile;
		$file->setStrictTypes(true);
		$file->setComment('This file was auto-generated');

		// Create class
		$file->addUse('Bckp\Translator\PluralProvider');
		$class = $file->addClass($className);
		$class->setExtends(\Bckp\Translator\Catalogue::class);
		$class->setImplements([ICatalogue::class]);
		$class->addComment("This file was auto-generated");

		// Setup plural method
		$method = $class->addMethod('plural');
		$plural = Method::from((array) $this->plural->getPlural($this->locale));

		$method->setParameters($plural->getParameters());
		$parameters = $method->getParameters();
		$method->setBody('return PluralProvider::?($?);', [$plural->getName(), key($parameters)]);
		$method->setReturnNullable($plural->isReturnNullable());
		$method->setReturnType($plural->getReturnType());

		// Messages & build time
		$class->addProperty('locale', $this->getLocale())->setVisibility('protected');
		$class->addProperty('build', time())->setVisibility('protected');
		$class->addProperty('messages', $messages)->setStatic(true)->setVisibility('protected');

		$code = (string) $file;
		$code .= "\nreturn new {$class->getName()};\n";

		// Return string
		return $code;
	}

	/**
	 * Get all messages
	 *
	 * @return array
	 * @throws FileInvalidException
	 */
	protected function getMessages(): array {
		$messages = [];
		foreach ($this->collection as $file) {
			$info = new \SplFileInfo($file);
			$resource = $info->getBasename('.' . $this->locale . '.neon');
			foreach ($this->loadFile($file) as $key => $item) {
				$messages[$resource . '.' . $key] = $item;
			}
		}

		return $messages;
	}

	/**
	 * @param string $file
	 * @return array
	 * @throws PathInvalidException
	 * @throws FileInvalidException
	 */
	protected function loadFile(string $file): array {
		if (!file_exists($file) || !is_readable($file))
			throw new PathInvalidException("File '{$file}' not found or is not readable.");

		$content = file_get_contents($file);
		if (!$content)
			return [];

		try {
			$content = Neon::decode($content);
			if (!is_array($content))
				throw new \Exception('No array');
		} catch (Throwable $e) {
			throw new FileInvalidException("File '{$file}' do not contain array of translations", $e->getCode(), $e);
		}
		return $content;
	}

	/**
	 * Link catalogue if not already linked
	 *
	 * @param string $filename
	 * @throws BuilderException
	 */
	private function link(string $filename): void {
		if ($this->loaded)
			return;

		/** @noinspection PhpIncludeInspection */
		$this->catalogue = include $filename;
		$this->loaded = true;
	}

	/**
	 * @param string $filename
	 */
	private function unlink(string $filename): void {
		@unlink($filename); // @ intentionally as file may not exists
		$this->loaded = false;
	}

	/**
	 * Enable debug mode
	 *
	 * @param bool $debug
	 * @return static
	 */
	public function setDebugMode(bool $debug): self {
		$this->debug = $debug;
		return $this;
	}

	/**
	 * @param string $file
	 * @return static
	 */
	public function addFile(string $file): self {
		$this->collection[] = $file;
		return $this;
	}
}
