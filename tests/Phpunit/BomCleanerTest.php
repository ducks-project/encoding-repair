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

use Ducks\Component\EncodingRepair\Cleaner\BomCleaner;
use PHPUnit\Framework\TestCase;

final class BomCleanerTest extends TestCase
{
    public function testGetPriority(): void
    {
        $cleaner = new BomCleaner();
        $this->assertSame(150, $cleaner->getPriority());
    }

    public function testIsAvailable(): void
    {
        $cleaner = new BomCleaner();
        $this->assertTrue($cleaner->isAvailable());
    }

    public function testCleanUtf8Bom(): void
    {
        $cleaner = new BomCleaner();
        $withBom = "\xEF\xBB\xBFCafé";
        $result = $cleaner->clean($withBom, 'UTF-8', []);

        $this->assertSame('Café', $result);
    }

    public function testCleanUtf16LeBom(): void
    {
        $cleaner = new BomCleaner();
        $withBom = "\xFF\xFEHello";
        $result = $cleaner->clean($withBom, 'UTF-16LE', []);

        $this->assertSame('Hello', $result);
    }

    public function testCleanUtf16BeBom(): void
    {
        $cleaner = new BomCleaner();
        $withBom = "\xFE\xFFHello";
        $result = $cleaner->clean($withBom, 'UTF-16BE', []);

        $this->assertSame('Hello', $result);
    }

    public function testCleanUtf32LeBom(): void
    {
        $cleaner = new BomCleaner();
        $withBom = "\xFF\xFE\x00\x00Test";
        $result = $cleaner->clean($withBom, 'UTF-32LE', []);

        $this->assertSame('Test', $result);
    }

    public function testCleanUtf32BeBom(): void
    {
        $cleaner = new BomCleaner();
        $withBom = "\x00\x00\xFE\xFFTest";
        $result = $cleaner->clean($withBom, 'UTF-32BE', []);

        $this->assertSame('Test', $result);
    }

    public function testCleanNoBom(): void
    {
        $cleaner = new BomCleaner();
        $noBom = 'Café';
        $result = $cleaner->clean($noBom, 'UTF-8', []);

        $this->assertNull($result);
    }

    public function testCleanEmptyString(): void
    {
        $cleaner = new BomCleaner();
        $result = $cleaner->clean('', 'UTF-8', []);

        $this->assertSame('', $result);
    }
}
