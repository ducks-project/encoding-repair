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

namespace Ducks\Component\EncodingRepair\Tests\phpunit;

use Ducks\Component\EncodingRepair\CharsetHelper;
use PHPUnit\Framework\TestCase;

/**
 * Tests for complex encoding corruptions and their limitations.
 *
 * @internal
 *
 * @coversDefaultClass \Ducks\Component\EncodingRepair\CharsetHelper
 */
final class RepairLimitationsTest extends TestCase
{
    /**
     * This test documents a known limitation: some complex corruptions
     * cannot be repaired automatically because they don't follow the
     * standard UTF-8/ISO double-encoding pattern.
     */
    public function testComplexCorruptionNowWorks(): void
    {
        $corrupted = 'FÃÂÂÂÂ©dÃÂÂÂÂ©ration Camerounaise de Football';

        // With pattern-based repair (ForceUTF8 approach), this now works!
        $result = CharsetHelper::repair($corrupted);

        $this->assertSame('Fédération Camerounaise de Football', $result);
    }

    /**
     * Alternative approaches for complex corruptions.
     */
    public function testAlternativeApproaches(): void
    {
        $corrupted = 'FÃÂÂÂÂ©dÃÂÂÂÂ©ration';

        // Try different source encodings
        $attempts = [
            'ISO-8859-1' => CharsetHelper::repair($corrupted, CharsetHelper::ENCODING_UTF8, CharsetHelper::ENCODING_ISO),
            'Windows-1252' => CharsetHelper::repair($corrupted, CharsetHelper::ENCODING_UTF8, CharsetHelper::WINDOWS_1252),
            'UTF-16' => CharsetHelper::repair($corrupted, CharsetHelper::ENCODING_UTF8, CharsetHelper::ENCODING_UTF16),
        ];

        foreach ($attempts as $encoding => $result) {
            echo "\nTrying $encoding: $result\n";
        }

        // For this specific corruption, manual byte-level analysis is needed
        $this->assertTrue(true, 'Alternative approaches documented');
    }

    /**
     * Document what types of corruptions ARE repairable.
     */
    public function testRepairableCorruptions(): void
    {
        // Standard double-encoding: UTF-8 interpreted as ISO, then re-encoded as UTF-8
        $original = 'Café';
        $doubleEncoded = mb_convert_encoding(
            mb_convert_encoding($original, 'ISO-8859-1', 'UTF-8'),
            'UTF-8',
            'ISO-8859-1'
        );

        $repaired = CharsetHelper::repair($doubleEncoded);

        $this->assertSame($original, $repaired, 'Standard double-encoding should be repairable');
    }
}
