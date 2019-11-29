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

/**
 * Interface IPlural
 *
 * @package Bckp\Translator
 */
interface IPlural
{
    /**
     * Plural variants
     */
    public const
        ZERO = 'zero',
        ONE = 'one',
        TWO = 'two',
        FEW = 'few',
        MANY = 'many',
        OTHER = 'other';

    /**
     * Get plural method
     * @param string $locale
     * @return callable
     */
    public function getPlural(string $locale): callable;
}
