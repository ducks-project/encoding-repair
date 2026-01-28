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

use Ducks\Component\EncodingRepair\Cleaner\Utf8FixerCleaner;
use PHPUnit\Framework\TestCase;

final class Utf8FixerCleanerTest extends TestCase
{
    public function testGetPriority(): void
    {
        $cleaner = new Utf8FixerCleaner();
        $this->assertSame(80, $cleaner->getPriority());
    }

    public function testIsAvailable(): void
    {
        $cleaner = new Utf8FixerCleaner();
        $this->assertTrue($cleaner->isAvailable());
    }

    public function testCleanDoubleEncoded(): void
    {
        $cleaner = new Utf8FixerCleaner();
        $broken = "CafÃ©";
        $result = $cleaner->clean($broken, 'UTF-8', []);

        $this->assertSame('Café', $result);
    }

    public function testCleanC383C2Pattern(): void
    {
        $cleaner = new Utf8FixerCleaner();
        $broken = "\xC3\x83\xC2\xA9";
        $result = $cleaner->clean($broken, 'UTF-8', []);

        $this->assertSame("\xC3\xA9", $result);
    }

    public function testCleanNoCorruption(): void
    {
        $cleaner = new Utf8FixerCleaner();
        $clean = 'Café';
        $result = $cleaner->clean($clean, 'UTF-8', []);

        $this->assertNull($result);
    }

    public function testCleanReturnsNullForNonUtf8(): void
    {
        $cleaner = new Utf8FixerCleaner();
        $data = 'Test';
        $result = $cleaner->clean($data, 'ISO-8859-1', []);

        $this->assertNull($result);
    }
}
