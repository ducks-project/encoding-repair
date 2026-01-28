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

use Ducks\Component\EncodingRepair\Cleaner\TransliterationCleaner;
use PHPUnit\Framework\TestCase;

final class TransliterationCleanerTest extends TestCase
{
    public function testGetPriority(): void
    {
        $cleaner = new TransliterationCleaner();
        $this->assertSame(30, $cleaner->getPriority());
    }

    public function testIsAvailable(): void
    {
        $cleaner = new TransliterationCleaner();
        $this->assertTrue($cleaner->isAvailable());
    }

    public function testCleanTransliteratesAccents(): void
    {
        $cleaner = new TransliterationCleaner();
        $accented = 'Café résumé';
        $result = $cleaner->clean($accented, 'UTF-8', []);

        $this->assertIsString($result);
        $this->assertStringContainsString('Cafe', $result);
    }

    public function testCleanAsciiOnly(): void
    {
        $cleaner = new TransliterationCleaner();
        $ascii = 'Hello World';
        $result = $cleaner->clean($ascii, 'UTF-8', []);

        $this->assertNull($result);
    }

    public function testCleanWithDifferentEncoding(): void
    {
        $cleaner = new TransliterationCleaner();
        $data = 'Café';
        $result = $cleaner->clean($data, 'ISO-8859-1', []);

        $this->assertIsString($result);
    }
}
