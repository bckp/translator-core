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
     * Plural provider
     * @var string[]
     */
    private $plurals = [
        'cs' => 'csPlural',
        'en' => 'enPlural',
        'id' => 'zeroPlural',
        'ja' => 'zeroPlural',
        'ka' => 'zeroPlural',
        'ko' => 'zeroPlural',
        'lo' => 'zeroPlural',
        'ms' => 'zeroPlural',
        'my' => 'zeroPlural',
        'th' => 'zeroPlural',
        'vi' => 'zeroPlural',
        'zh' => 'zeroPlural',
    ];

    /**
     * Czech plural selector (zero-one-few-other)
     *
     * @param int|null $n
     * @return string
     */
    public static function csPlural(?int $n): string
    {
        return $n === 0
            ? IPlural::ZERO
            : ($n === 1
                ? IPlural::ONE
                : ($n >= 2 && $n < 5
                    ? IPlural::FEW
                    : IPlural::OTHER
                )
            );
    }

    /**
     * Default plural detector (zero-one-other)
     *
     * @param int|null $n
     * @return string
     */
    public static function enPlural(?int $n): string
    {
        return $n === 0
            ? IPlural::ZERO
            : ($n === 1
                ? IPlural::ONE
                : IPlural::OTHER
            );
    }

    /**
     * No plural detector (zero-other)
     *
     * @param int|null $n
     * @return string
     */
    public static function zeroPlural(?int $n): string
    {
        return $n === 0
            ? IPlural::ZERO
            : IPlural::OTHER;
    }

    /**
     * Get plural method
     *
     * @param string $locale
     * @return callable(int|null $n): string
     */
    public function getPlural(string $locale): callable
    {
        $locale = strtolower($locale);
        $callable = [$this, $this->plurals[$locale] ?? null];

        if (is_callable($callable)) {
            return $callable;
        }
        return [$this, 'enPlural'];
    }
}
