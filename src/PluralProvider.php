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

final class PluralProvider
{
    public const Default = 'enPlural';
    private array $plurals = [
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
     */
    public static function csPlural(?int $n): Plural
    {
        return match (true) {
            $n === 0 => Plural::Zero,
            $n === 1 => Plural::One,
            $n >= 2 && $n <= 4 => Plural::Few,
            default => Plural::Other,
        };
    }

    /**
     * Default plural detector (zero-one-other)
     */
    public static function enPlural(?int $n): Plural
    {
        return match (true) {
            $n === 0 => Plural::Zero,
            $n === 1 => Plural::One,
            default => Plural::Other,
        };
    }

    /**
     * No plural detector (zero-other)
     */
    public static function zeroPlural(?int $n): Plural
    {
        return $n === 0
            ? Plural::Zero
            : Plural::Other;
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

        if ($callable[1] && is_callable($callable)) {
            return $callable;
        }
        return [$this, self::Default];
    }
}
