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

use Ducks\Component\EncodingRepair\Cleaner\WhitespaceCleaner;
use PHPUnit\Framework\TestCase;

final class WhitespaceCleanerTest extends TestCase
{
    public function testGetPriority(): void
    {
        $cleaner = new WhitespaceCleaner();
        $this->assertSame(40, $cleaner->getPriority());
    }

    public function testIsAvailable(): void
    {
        $cleaner = new WhitespaceCleaner();
        $this->assertTrue($cleaner->isAvailable());
    }

    public function testCleanMultipleSpaces(): void
    {
        $cleaner = new WhitespaceCleaner();
        $messy = 'Text  with   multiple    spaces';
        $result = $cleaner->clean($messy, 'UTF-8', []);

        $this->assertSame('Text with multiple spaces', $result);
    }

    public function testCleanTabsAndNbsp(): void
    {
        $cleaner = new WhitespaceCleaner();
        $messy = "Text\t\xC2\xA0with\ttabs";
        $result = $cleaner->clean($messy, 'UTF-8', []);

        $this->assertIsString($result);
        $this->assertStringContainsString('Text with tabs', $result);
    }

    public function testCleanAlreadyNormalized(): void
    {
        $cleaner = new WhitespaceCleaner();
        $clean = 'Text with single spaces';
        $result = $cleaner->clean($clean, 'UTF-8', []);

        $this->assertNull($result);
    }
}
