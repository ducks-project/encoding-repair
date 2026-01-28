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

use Ducks\Component\EncodingRepair\Cleaner\NormalizerCleaner;
use PHPUnit\Framework\TestCase;

final class NormalizerCleanerTest extends TestCase
{
    public function testGetPriority(): void
    {
        $cleaner = new NormalizerCleaner();
        $this->assertSame(90, $cleaner->getPriority());
    }

    public function testIsAvailable(): void
    {
        $cleaner = new NormalizerCleaner();
        $this->assertTrue($cleaner->isAvailable());
    }

    public function testCleanDecomposedCharacters(): void
    {
        $cleaner = new NormalizerCleaner();
        // e + combining acute accent
        $decomposed = "Cafe\u{0301}";
        $result = $cleaner->clean($decomposed, 'UTF-8', []);

        $this->assertIsString($result);
        $this->assertSame('Café', $result);
    }

    public function testCleanAlreadyNormalized(): void
    {
        $cleaner = new NormalizerCleaner();
        $normalized = 'Café';
        $result = $cleaner->clean($normalized, 'UTF-8', []);

        $this->assertSame($normalized, $result);
    }

    public function testCleanReturnsNullForNonUtf8(): void
    {
        $cleaner = new NormalizerCleaner();
        $data = 'Test';
        $result = $cleaner->clean($data, 'ISO-8859-1', []);

        $this->assertNull($result);
    }

    public function testCleanCaseInsensitiveEncoding(): void
    {
        $cleaner = new NormalizerCleaner();
        $data = 'Café';
        $result = $cleaner->clean($data, 'utf-8', []);

        $this->assertIsString($result);
    }
}
