<?php

/**
 * Part of EncodingRepair package.
 *
 * (c) Adrien Loyant <donald_duck@team-df.org>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types=1);

namespace Ducks\Component\EncodingRepair\Tests\common;

final class Word
{
    /**
     * @var list<string>
     */
    public const GOOD = [
        'Café',
        'Thé',
        'Étoile',
        'déjà',
        'lièvre',
        'euro €',
        'test',
    ];

    /**
     * Utf8 based opened with WP1252
     *
     * @var list<string>
     */
    public const BAD = [
        'CafÃ©',
        'ThÃ©',
        'Ã‰toile',
        'dÃ©jÃ ',
        'liÃ¨vre',
        'euro â‚¬',
        'test'
    ];

    public static function getBadUtf8Word(): string
    {
        return self::BAD[0];
    }

    public static function getGoodUtf8Word(): string
    {
        return self::GOOD[0];
    }
}
