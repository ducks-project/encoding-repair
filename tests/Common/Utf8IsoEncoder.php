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

namespace Ducks\Component\EncodingRepair\Tests\Common;

/**
 * UTF-8 to ISO-8859-1 encoder/decoder for testing purposes.
 *
 * Provides fast in-place encoding/decoding between UTF-8 and ISO-8859-1.
 * Used in test fixtures to generate test data.
 *
 * @internal Test utility class
 * @psalm-immutable
 */
final class Utf8IsoEncoder
{
    /**
     * Encode ISO-8859-1 string to UTF-8.
     *
     * Converts ISO-8859-1 (Latin-1) bytes to UTF-8 encoding.
     * Uses in-place buffer manipulation for performance.
     *
     * @param string $string ISO-8859-1 encoded string
     *
     * @return string UTF-8 encoded string
     */
    public static function encode(string $string): string
    {
        $string .= $string;
        $len = \strlen($string);

        for ($i = $len >> 1, $j = 0; $i < $len; ++$i, ++$j) {
            switch (true) {
                case $string[$i] < "\x80":
                    $string[$j] = $string[$i];
                    break;
                case $string[$i] < "\xC0":
                    $string[$j] = "\xC2";
                    $string[++$j] = $string[$i];
                    break;
                default:
                    $string[$j] = "\xC3";
                    $string[++$j] = \chr(\ord($string[$i]) - 64);
                    break;
            }
        }

        return \substr($string, 0, $j);
    }

    /**
     * Decode UTF-8 string to ISO-8859-1.
     *
     * Converts UTF-8 bytes to ISO-8859-1 (Latin-1) encoding.
     * Replaces unmappable characters with '?'.
     *
     * @param string $string UTF-8 encoded string
     *
     * @return string ISO-8859-1 encoded string
     */
    public static function decode(string $string): string
    {
        $string .= $string;
        $len = \strlen($string);

        for ($i = 0, $j = 0; $i < $len; ++$i, ++$j) {
            switch ($string[$i] & "\xF0") {
                case "\xC0":
                case "\xD0":
                    $c = (\ord($string[$i] & "\x1F") << 6) | \ord($string[++$i] & "\x3F");
                    $string[$j] = 256 > $c ? \chr($c) : '?';
                    break;

                case "\xF0":
                    ++$i;
                    // no break

                case "\xE0":
                    $string[$j] = '?';
                    $i += 2;
                    break;

                default:
                    $string[$j] = $string[$i];
            }
        }

        return \substr($string, 0, $j);
    }

    /**
     * Generate corrupted UTF-8 string by simulating double-encoding.
     *
     * Simulates the corruption that occurs when UTF-8 bytes are misinterpreted
     * as ISO-8859-1 and re-encoded as UTF-8 multiple times.
     * Uses internal encode() method to avoid mbstring dependency.
     *
     * Examples:
     * - "café" with 1 pass → "cafÃ©"
     * - "café" with 2 passes → "cafÃÂ©"
     * - "café" with 3 passes → "cafÃÂÃÂ©"
     *
     * @param string $string Original UTF-8 string
     * @param int $depth Number of corruption passes (default: 1, max: 10)
     *
     * @return string Corrupted UTF-8 string
     */
    public static function generateCorruptedUtf8(string $string, int $depth = 1): string
    {
        $depth = \max(1, \min(10, $depth));

        for ($i = 0; $i < $depth; ++$i) {
            $string = self::encode($string);
        }

        return $string;
    }
}
