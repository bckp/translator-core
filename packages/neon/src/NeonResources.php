<?php

declare(strict_types=1);

namespace Bckp\Translator\Neon;

use Closure;
use stdClass;

final readonly class NeonResources
{
	private stdClass $resources;

	public function __construct(NeonResource ...$resources)
	{
		$record = new stdClass();
		foreach ($resources as $resource) {
			$record->{$resource->path} = $resource;
		}
		$this->resources = $record;
	}

	public function each(Closure $consumer): void
	{
		foreach (get_object_vars($this->resources) as $resource) {
			$consumer($resource);
		}
	}
}
