<?php

declare(strict_types=1);

namespace Bckp\Translator\PhpCache;

use Bckp\Translator\PluralProvider;
use Bckp\Translator\PluralRule;
use PhpToken;
use ReflectionMethod;
use RuntimeException;

final class PluralMethodBody
{
	public static function extract(PluralRule $rule): string
	{
		$name = match ($rule) {
			PluralRule::Czech => 'csPlural',
			PluralRule::English => 'enPlural',
			PluralRule::Invariant => 'zeroPlural',
		};
		$method = new ReflectionMethod(PluralProvider::class, $name);
		$file = $method->getFileName();
		$source = $file === false ? false : file_get_contents($file);

		if ($source === false) {
			throw new RuntimeException("Cannot read source for plural rule '$name'.");
		}
		$start = null;
		$depth = 0;
		$startLine = $method->getStartLine();
		$endLine = $method->getEndLine();
		foreach (PhpToken::tokenize($source) as $token) {
			if ($token->line < $startLine) {
				continue;
			}

			if ($token->line > $endLine) {
				break;
			}

			if ($token->is('{')) {
				$start ??= $token->pos + 1;
				++$depth;
				continue;
			}

			if ($token->is('}')) {
				--$depth;

				if ($depth === 0 && $start !== null) {
					return ltrim(rtrim(substr($source, $start, $token->pos - $start)), "\r\n");
				}
			}
		}

		throw new RuntimeException("Cannot extract body for plural rule '$name'.");
	}
}
