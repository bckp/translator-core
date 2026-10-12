<?php

declare(strict_types=1);

namespace Bckp\Translator;

use Closure;
use InvalidArgumentException;
use stdClass;

final readonly class SourceVersions
{
	private stdClass $versions;

	private string $fingerprint;

	public function __construct(SourceVersion ...$versions)
	{
		$record = new stdClass();
		$signature = '';
		foreach ($versions as $version) {
			if (property_exists($record, $version->id)) {
				throw new InvalidArgumentException("Duplicate source: {$version->id}");
			}
			$record->{$version->id} = $version;
			$signature .= strlen($version->id) . ':' . $version->id
				. strlen($version->version) . ':' . $version->version;
		}
		$this->versions = $record;
		$this->fingerprint = hash('sha256', $signature);
	}

	public function equals(self $other): bool
	{
		return $this->fingerprint === $other->fingerprint;
	}

	public function get(string $id): ?string
	{
		$version = $this->versions->{$id} ?? null;

		return $version instanceof SourceVersion ? $version->version : null;
	}

	public function each(Closure $consumer): void
	{
		foreach (get_object_vars($this->versions) as $version) {
			$consumer($version);
		}
	}
}
