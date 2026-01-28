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

use Ducks\Component\EncodingRepair\Traits\CleanerTrait;

/**
 * Cleaner using preg_replace to remove invalid UTF-8 sequences.
 *
 * @psalm-api
 */
final class PregMatchCleaner implements CleanerInterface
{
    use CleanerTrait;

    /**
     * Cleans invalid sequences from string.
     *
     * @param string $data String to clean
     * @param string $encoding Target encoding for validation
     * @param array<string, mixed> $options Cleaning options
     *
     * @return ?string Cleaned string or null if cleaner cannot handle
     */
    protected function doClean(string $data, string $encoding, array $options): ?string
    {
        if ('UTF-8' !== \strtoupper($encoding)) {
            return null;
        }

        // Remove invalid UTF-8 sequences
        $cleaned = @\preg_replace('/[\x00-\x08\x0B\x0C\x0E-\x1F\x7F]/', '', $data);

        return null !== $cleaned ? $cleaned : null;
    }

    public function getPriority(): int
    {
        return 50;
    }

    public function isAvailable(): bool
    {
        return true;
    }
}
