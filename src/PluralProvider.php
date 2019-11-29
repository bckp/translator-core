<?php

/**
 * BCKP Translator
 * (c) Radovan Kepák
 *
 * For the full copyright and license information, please view
 * the file license.md that was distributed with this source code.
 *
 * @author Radovan Kepak <radovan@kepak.eu>
 */

declare(strict_types=1);

namespace Bckp\Translator;

use function strtolower;

/**
 * Class PluralProvider
 *
 * @package Bckp\Translator
 */
final class PluralProvider implements IPlural
{
    /**
     * Czech plural selector (zero-one-few-other)
     *
     * @param int $n
     * @return string
     */
    public static function csPlural(?int $n): string
    {
        return
            $n === 0 ? IPlural::ZERO : ($n === 1 ? IPlural::ONE : ($n >= 2 && $n < 5 ? IPlural::FEW : IPlural::OTHER));
    }

    /**
     * Default plural detector (zero-one-other)
     *
     * @param int|null $n
     * @return string
     */
    public static function enPlural(?int $n): string
    {
        return ($n === 0 ? IPlural::ZERO : ($n === 1 ? IPlural::ONE : IPlural::OTHER));
    }

    /**
     * No plural detector (zero-other)
     *
     * @param int $n
     * @return string
     */
    public static function zeroPlural(?int $n): string
    {
        return ($n === 0 ? IPlural::ZERO : IPlural::OTHER);
    }

    /**
     * Get plural method
     *
     * @param string $locale
     * @return callable
     */
    public function getPlural(string $locale): callable
    {
        switch (strtolower($locale)) {
            default:
                return [$this, 'enPlural'];
            case 'id':
            case 'ja':
            case 'ka':
            case 'ko':
            case 'lo':
            case 'ms':
            case 'my':
            case 'th':
            case 'vi':
            case 'zh':
                return [$this, 'zeroPlural'];
            case 'cs':
                return [$this, 'csPlural'];
            //TODO: Add other languages plural selectors
        }
    }
}
