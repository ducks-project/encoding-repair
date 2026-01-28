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

namespace Ducks\Component\EncodingRepair\Cleaner;

/**
 * Cleaner that repairs light UTF-8 corruption (ForceUTF8 approach).
 *
 * @psalm-api
 */
final class Utf8FixerCleaner implements CleanerInterface
{
    public function clean(string $data, string $encoding, array $options): ?string
    {
        if ('UTF-8' !== \strtoupper($encoding)) {
            return null;
        }

        // Quick check: if no corruption patterns, skip
        if (false === \strpos($data, "\xC3\x82") && false === \strpos($data, "\xC3\x83")) {
            return null;
        }

        // Repair double-encoded patterns
        $fixed = \preg_replace(
            ['/\xC3\x82/', '/\xC3\x83\xC2([\xA0-\xFF])/'],
            ['', "\xC3$1"],
            $data
        );

        return null !== $fixed && $fixed !== $data ? $fixed : null;
    }

    public function getPriority(): int
    {
        return 80;
    }

    public function isAvailable(): bool
    {
        return true;
    }
}
