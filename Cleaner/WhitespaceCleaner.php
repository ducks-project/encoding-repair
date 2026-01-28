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
 * Cleaner that normalizes whitespace characters.
 *
 * @psalm-api
 */
final class WhitespaceCleaner implements CleanerInterface
{
    public function clean(string $data, string $encoding, array $options): ?string
    {
        // Replace multiple spaces, tabs, NBSP with single space
        $cleaned = \preg_replace('/[\s\xC2\xA0]+/u', ' ', $data);

        return null !== $cleaned && $cleaned !== $data ? $cleaned : null;
    }

    public function getPriority(): int
    {
        return 40;
    }

    public function isAvailable(): bool
    {
        return true;
    }
}
