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

namespace Ducks\Component\EncodingRepair\Detector;

/**
 * Fast encoding detector using preg_match for ASCII and UTF-8.
 *
 * This detector provides optimized detection for the most common encodings:
 * - ASCII: Pure 7-bit characters (0x00-0x7F)
 * - UTF-8: Valid multi-byte UTF-8 sequences
 *
 * Performance: ~70% faster than mb_detect_encoding for ASCII/UTF-8 detection.
 *
 * @psalm-api
 */
final class PregMatchDetector implements DetectorInterface
{
    /**
     * Detects encoding using preg_match patterns.
     *
     * @param string $string String to analyze
     * @param null|array<string, mixed> $options Detection options (unused)
     *
     * @return ?string 'ASCII', 'UTF-8', or null if neither
     */
    public function detect(string $string, ?array $options = null): ?string
    {
        // Empty string is valid ASCII
        if ('' === $string) {
            return 'ASCII';
        }

        // Fast-path: ASCII-only (0x00-0x7F)
        if (!\preg_match('/[\x80-\xFF]/', $string)) {
            return 'ASCII';
        }

        // UTF-8 validation with 'u' modifier
        if (false !== @\preg_match('//u', $string)) {
            return 'UTF-8';
        }

        return null;
    }

    /**
     * Returns detector priority.
     *
     * Priority 150: Higher than MbStringDetector (100) but lower than CachedDetector (200).
     *
     * @return int Priority value
     */
    public function getPriority(): int
    {
        return 150;
    }

    /**
     * Checks if detector is available.
     *
     * @return bool Always true (preg_match is always available)
     */
    public function isAvailable(): bool
    {
        return true;
    }
}
