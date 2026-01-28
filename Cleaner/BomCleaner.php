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
 * Cleaner that removes BOM (Byte Order Mark) from strings.
 *
 * @psalm-api
 */
final class BomCleaner implements CleanerInterface
{
    private const BOM_UTF8 = "\xEF\xBB\xBF";
    private const BOM_UTF16_BE = "\xFE\xFF";
    private const BOM_UTF16_LE = "\xFF\xFE";
    private const BOM_UTF32_BE = "\x00\x00\xFE\xFF";
    private const BOM_UTF32_LE = "\xFF\xFE\x00\x00";

    public function clean(string $data, string $encoding, array $options): ?string
    {
        if ('' === $data) {
            return $data;
        }

        // Check UTF-32 BOMs first (4 bytes)
        if (\strlen($data) >= 4) {
            if (0 === \strpos($data, self::BOM_UTF32_BE) || 0 === \strpos($data, self::BOM_UTF32_LE)) {
                return \substr($data, 4);
            }
        }

        // Check UTF-8 BOM (3 bytes)
        if (\strlen($data) >= 3 && 0 === \strpos($data, self::BOM_UTF8)) {
            return \substr($data, 3);
        }

        // Check UTF-16 BOMs (2 bytes)
        if (\strlen($data) >= 2) {
            if (0 === \strpos($data, self::BOM_UTF16_BE) || 0 === \strpos($data, self::BOM_UTF16_LE)) {
                return \substr($data, 2);
            }
        }

        return null;
    }

    public function getPriority(): int
    {
        return 150;
    }

    public function isAvailable(): bool
    {
        return true;
    }
}
