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

namespace Ducks\Component\EncodingRepair\Tests\Phpunit;

use Ducks\Component\EncodingRepair\Cleaner\IconvCleaner;
use PHPUnit\Framework\TestCase;

final class IconvCleanerTest extends TestCase
{
    public function testGetPriority(): void
    {
        $cleaner = new IconvCleaner();
        $this->assertSame(10, $cleaner->getPriority());
    }

    public function testIsAvailable(): void
    {
        $cleaner = new IconvCleaner();
        $this->assertTrue($cleaner->isAvailable());
    }

    public function testCleanCorruptedData(): void
    {
        $cleaner = new IconvCleaner();
        // Invalid UTF-8 byte sequence - iconv returns false
        $corrupted = "Caf\xE9";
        $result = $cleaner->clean($corrupted, 'UTF-8', []);

        // iconv fails with invalid input, returns null
        $this->assertNull($result);
    }

    public function testCleanWithControlCharacters(): void
    {
        $cleaner = new IconvCleaner();
        // Valid UTF-8 with control characters
        $data = "Test\x00\x1Fdata";
        $result = $cleaner->clean($data, 'UTF-8', []);

        // iconv with //IGNORE removes control chars
        $this->assertIsString($result);
        $this->assertStringContainsString('Test', $result);
    }

    public function testCleanValidUtf8(): void
    {
        $cleaner = new IconvCleaner();
        $valid = 'Café résumé';
        $result = $cleaner->clean($valid, 'UTF-8', []);

        $this->assertSame($valid, $result);
    }

    public function testCleanWithDifferentEncoding(): void
    {
        $cleaner = new IconvCleaner();
        $data = 'Test data';
        $result = $cleaner->clean($data, 'ISO-8859-1', []);

        $this->assertIsString($result);
    }
}
