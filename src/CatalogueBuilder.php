<?php

declare(strict_types=1);

/**
 * BCKP Translator
 * (c) Radovan Kepák
 *
 * For the full copyright and license information, please view
 * the file license.md that was distributed with this source code.
 *
 * @author Radovan Kepak <radovan@kepak.dev>
 */

namespace Bckp\Translator;

use Bckp\Translator\Exceptions\BuilderException;
use Bckp\Translator\Exceptions\FileInvalidException;
use Bckp\Translator\Exceptions\PathInvalidException;
use Nette\Neon\Neon;
use Nette\PhpGenerator\ClassType;
use Nette\PhpGenerator\Method;
use Nette\PhpGenerator\PhpFile;
use RuntimeException;
use SplFileInfo;
use Throwable;

use function file_exists;
use function file_get_contents;
use function file_put_contents;
use function filemtime;
use function is_readable;
use function is_writable;
use function strtolower;
use function time;
use function unlink;

final class CatalogueBuilder
{
	/** @var callable[] */
	public array $onCompile = [];

	/** @var callable[] */
	public array $onCheck = [];

	/** @var callable[] */
	private array $dynamic = [];

	private ?Catalogue $catalogue = null;

	/** @var array<string> */
	private array $collection = [];

	private bool $debug = false;
	private bool $loaded = false;
	private string $locale;

	/**
	 * @api
	 */
	public function __construct(
		private readonly PluralProvider $plural,
		private readonly string $path,
		string $locale
	) {
		if (!is_writable($path)) {
			throw new PathInvalidException("Path '$path' is not writable.");
		}

		$this->locale = strtolower($locale);
	}

	/**
	 * @api
	 */
	public function addFile(string $file): self
	{
		$this->collection[] = $file;
		return $this;
	}

	/**
	 * @api
	 */
	public function addDynamic(string $resource, callable $callback): self
	{
		$this->dynamic[strtolower($resource)] = $callback;
		return $this;
	}

	/**
	 * @api
	 * @throws Throwable
	 */
	public function rebuild(int $attempt = 0): Catalogue
	{
		$filename = $this->path . '/' . $this->getName() . '.php';
		$this->unlink($filename);
		return $this->compile($attempt);
	}

	protected function getName(): string
	{
		return $this->locale . 'Catalogue';
	}

	private function unlink(string $filename): void
	{
		/** @scrutinizer ignore-unhandled */
		@unlink($filename); // @ intentionally as file may not exist
		$this->loaded = false;
	}

	/**
	 * @throws BuilderException
	 */
	public function compile(int $rebuild = 0): Catalogue
	{
		// Exception on to many rebuild try
		if ($rebuild > 3) {
			throw new BuilderException('Failed to build language file');
		}

		// Check for file exist, create if no
		$filename = $this->path . '/' . $this->getName() . '.php';
		if (!file_exists($filename)) {
			file_put_contents($filename, $this->compileCode());
		}

		// Link file and rebuild if error
		try {
			$this->checkForChanges($filename);
			$this->link($filename);

			if (!$this->catalogue instanceof Catalogue) {
				throw new BuilderException('Catalogue is not implementing Catalogue');
			}

			return $this->catalogue;
		} catch (Throwable $e) {
			$this->unlink($filename);
			return $this->compile(++$rebuild);
		}
	}

	protected function checkForChanges(string $filename): void
	{
		if (!$this->debug) {
			return;
		}

		$cacheTime = (int) filemtime($filename);
		foreach ($this->collection as $file) {
			$file = new SplFileInfo($file);
			$fileTime = $file->getMTime();
			if ($fileTime > $cacheTime || ($this->catalogue && $fileTime > $this->catalogue->build)) {
				throw new BuilderException('Rebuild required');
			}
		}

		$this->onCheck($cacheTime);
	}

	protected function compileCode(): string
	{
		// Load messages, then generate code
		$messages = $this->getMessages();
		$this->onCompile($messages);

		// File
		$file = new PhpFile();
		$file->setStrictTypes();
		$file->setComment('This file was auto-generated');

		$class = new ClassType();
		$class->setExtends(Catalogue::class);

		// Setup plural method
		$method = $class->addMethod('plural');
		$plural = Method::from((array) $this->plural->getPlural($this->locale));
		$method->setParameters($plural->getParameters());
		$parameters = $method->getParameters();
		$method->setBody('return Bckp\Translator\PluralProvider::?($?);', [$plural->getName(), key($parameters)]);
		$method->setReturnNullable($plural->isReturnNullable());

		/** @psalm-suppress PossiblyInvalidArgument getReturnType return string if not argument present */
		$method->setReturnType($plural->getReturnType());

		$class->addProperty('messages', $messages)->setType('array')->setStatic()->setVisibility('protected');

        $build = time();
        $locale = $this->getLocale();

		// Generate code
		$code = (string) $file;
		$code .= "\nreturn new class (locale: '{$locale}', build: {$build}) {$class};\n";

		// Return string
		return $code;
	}

	/**
	 * @return string[]
	 * @throws FileInvalidException
	 */
	protected function getMessages(): array
	{
		$messages = [];

		// Add files
		foreach ($this->collection as $file) {
			$info = new SplFileInfo($file);
			$resource = strtolower($info->getBasename('.' . $this->locale . '.neon'));
			foreach ($this->loadFile($file) as $key => $item) {
				$messages[$resource . '.' . $key] = $item;
			}
		}

		// Add dynamic translations
		foreach ($this->dynamic as $resource => $callback) {
			$resource = $namespace = strtolower($resource);
			$locale = $this->locale;
			$array = [];

			$callback($array, $resource, $locale);

			// @phpstan-ignore-next-line
			foreach ($array as $key => $item) {
				$messages[$namespace . '.' . $key] = $item;
			}
		}

		return $messages;
	}

	/**
	 * @return string[]
	 * @throws PathInvalidException
	 * @throws FileInvalidException
	 */
	protected function loadFile(string $file): array
	{
		$content = $this->readFile($file);
		if ($content === null) {
			return [];
		}

		try {
			$data = Neon::decode($content);
			if (!is_array($data)) {
				throw new RuntimeException('No array');
			}

			return $data;
		} catch (Throwable $e) {
			throw new FileInvalidException(
				"File '$file' do not contain array of translations",
				(int) $e->getCode(),
				$e
			);
		}
	}

	private function readFile(string $file): ?string
	{
		if (!file_exists($file) || !is_readable($file)) {
			throw new PathInvalidException("File '$file' not found or is not readable.");
		}

		return file_get_contents($file) ?: null;
	}

	/**
	 * Occurs when new catalogue is compiled, after all strings are loaded
	 * @param array<array<string>|string> $messages
	 */
	private function onCompile(array &$messages): void
	{
		foreach ($this->onCompile as $callback) {
			$locale = $this->locale;
			$callback($messages, $locale);
		}
	}

	/**
	 * @api
	 */
	public function getLocale(): string
	{
		return $this->locale;
	}

	private function onCheck(int $fileTime): void
	{
		foreach ($this->onCheck as $callback) {
			$locale = $this->locale;
			$callback($fileTime, $locale);
		}
	}

	/**
	 * Link catalogue if not already linked
	 *
	 * @param string $filename
	 * @throws BuilderException
	 */
	private function link(string $filename): void
	{
		if ($this->loaded) {
			return;
		}

		/** @psalm-suppress UnresolvableInclude */
		$this->catalogue = require $filename;
		$this->loaded = true;
	}

	/**
	 * @api
	 * @param callable $callback function(array &$messages, string $locale): void
	 */
	public function addCompileCallback(callable $callback): void
	{
		$this->onCompile[] = $callback;
	}

	/**
	 * @api
	 * @param callable $callback function(string $locale): void
	 */
	public function addCheckCallback(callable $callback): void
	{
		$this->onCheck[] = $callback;
	}

	/**
	 * @api
	 */
	public function setDebugMode(bool $debug): self
	{
		$this->debug = $debug;
		return $this;
	}
}
