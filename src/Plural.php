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

enum Plural: string
{
	case Zero = 'zero';
	case One = 'one';
	case Two = 'two';
	case Few = 'few';
	case Many = 'many';
	case Other = 'other';
}
