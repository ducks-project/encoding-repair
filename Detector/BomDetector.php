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
 * BOM (Byte Order Mark) detector for UTF encodings.
 *
 * Detects encoding by analyzing the Byte Order Mark at the beginning of the string.
 * This is the most reliable detection method when BOM is present.
 *
 * Supported BOMs:
 * - UTF-8: EF BB BF
 * - UTF-16 LE: FF FE
 * - UTF-16 BE: FE FF
 * - UTF-32 LE: FF FE 00 00
 * - UTF-32 BE: 00 00 FE FF
 *
 * @psalm-api
 */
final class BomDetector implements DetectorInterface
{
    /**
     * Detects encoding by BOM signature.
     *
     * @param string $string String to analyze
     * @param null|array<string, mixed> $options Detection options (unused)
     *
     * @return ?string Detected encoding or null if no BOM found
     */
    public function detect(string $string, ?array $options = null): ?string
    {
        $length = \strlen($string);

        if ($length < 2) {
            return null;
        }

        // UTF-32 LE BOM: FF FE 00 00 (must check before UTF-16 LE)
        if ($length >= 4
            && "\xFF" === $string[0]
            && "\xFE" === $string[1]
            && "\x00" === $string[2]
            && "\x00" === $string[3]
        ) {
            return 'UTF-32LE';
        }

        // UTF-32 BE BOM: 00 00 FE FF
        if ($length >= 4
            && "\x00" === $string[0]
            && "\x00" === $string[1]
            && "\xFE" === $string[2]
            && "\xFF" === $string[3]
        ) {
            return 'UTF-32BE';
        }

        // UTF-8 BOM: EF BB BF
        if ($length >= 3
            && "\xEF" === $string[0]
            && "\xBB" === $string[1]
            && "\xBF" === $string[2]
        ) {
            return 'UTF-8';
        }

        // UTF-16 LE BOM: FF FE
        if ("\xFF" === $string[0] && "\xFE" === $string[1]) {
            return 'UTF-16LE';
        }

        // UTF-16 BE BOM: FE FF
        if ("\xFE" === $string[0] && "\xFF" === $string[1]) {
            return 'UTF-16BE';
        }

        return null;
    }

    /**
     * Returns detector priority.
     *
     * Priority 160: Highest priority (BOM is the most reliable detection method).
     *
     * @return int Priority value
     */
    public function getPriority(): int
    {
        return 160;
    }

    /**
     * Checks if detector is available.
     *
     * @return bool Always true (no dependencies)
     */
    public function isAvailable(): bool
    {
        return true;
    }
}
