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

use Ducks\Component\EncodingRepair\Cleaner\MbScrubCleaner;
use PHPUnit\Framework\TestCase;

final class MbScrubCleanerTest extends TestCase
{
    public function testGetPriority(): void
    {
        $cleaner = new MbScrubCleaner();
        $this->assertSame(100, $cleaner->getPriority());
    }

    public function testIsAvailable(): void
    {
        $cleaner = new MbScrubCleaner();
        $this->assertTrue($cleaner->isAvailable());
    }

    public function testCleanCorruptedUtf8(): void
    {
        $cleaner = new MbScrubCleaner();
        $corrupted = "Caf\xC3\xA9 \xC2\x88 invalid";
        $result = $cleaner->clean($corrupted, 'UTF-8', []);

        $this->assertIsString($result);
        $this->assertStringContainsString('Caf', $result);
    }

    public function testCleanValidUtf8(): void
    {
        $cleaner = new MbScrubCleaner();
        $valid = 'Café résumé';
        $result = $cleaner->clean($valid, 'UTF-8', []);

        $this->assertSame($valid, $result);
    }

    public function testCleanWithDifferentEncoding(): void
    {
        $cleaner = new MbScrubCleaner();
        $data = 'Test data';
        $result = $cleaner->clean($data, 'ISO-8859-1', []);

        $this->assertIsString($result);
    }
}
