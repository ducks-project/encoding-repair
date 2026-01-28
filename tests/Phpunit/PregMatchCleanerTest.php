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

use Ducks\Component\EncodingRepair\Cleaner\PregMatchCleaner;
use PHPUnit\Framework\TestCase;

final class PregMatchCleanerTest extends TestCase
{
    public function testGetPriority(): void
    {
        $cleaner = new PregMatchCleaner();
        $this->assertSame(50, $cleaner->getPriority());
    }

    public function testIsAvailable(): void
    {
        $cleaner = new PregMatchCleaner();
        $this->assertTrue($cleaner->isAvailable());
    }

    public function testCleanRemovesControlCharacters(): void
    {
        $cleaner = new PregMatchCleaner();
        $data = "Text\x00\x1F\x7F";
        $result = $cleaner->clean($data, 'UTF-8', []);

        $this->assertSame('Text', $result);
    }

    public function testCleanValidUtf8(): void
    {
        $cleaner = new PregMatchCleaner();
        $valid = 'Café résumé';
        $result = $cleaner->clean($valid, 'UTF-8', []);

        $this->assertSame($valid, $result);
    }

    public function testCleanReturnsNullForNonUtf8(): void
    {
        $cleaner = new PregMatchCleaner();
        $data = 'Test data';
        $result = $cleaner->clean($data, 'ISO-8859-1', []);

        $this->assertNull($result);
    }

    public function testCleanCaseInsensitiveEncoding(): void
    {
        $cleaner = new PregMatchCleaner();
        $data = 'Test data';
        $result = $cleaner->clean($data, 'utf-8', []);

        $this->assertIsString($result);
    }
}
