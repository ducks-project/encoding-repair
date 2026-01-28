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

use Ducks\Component\EncodingRepair\Cleaner\HtmlEntityCleaner;
use PHPUnit\Framework\TestCase;

final class HtmlEntityCleanerTest extends TestCase
{
    public function testGetPriority(): void
    {
        $cleaner = new HtmlEntityCleaner();
        $this->assertSame(60, $cleaner->getPriority());
    }

    public function testIsAvailable(): void
    {
        $cleaner = new HtmlEntityCleaner();
        $this->assertTrue($cleaner->isAvailable());
    }

    public function testCleanHtmlEntities(): void
    {
        $cleaner = new HtmlEntityCleaner();
        $encoded = 'Caf&eacute; &amp; R&eacute;sum&eacute;';
        $result = $cleaner->clean($encoded, 'UTF-8', []);

        $this->assertSame('Café & Résumé', $result);
    }

    public function testCleanDoubleEncodedEntities(): void
    {
        $cleaner = new HtmlEntityCleaner();
        $doubleEncoded = '&amp;eacute;';
        $result = $cleaner->clean($doubleEncoded, 'UTF-8', []);

        $this->assertSame('&eacute;', $result);
    }

    public function testCleanNumericEntities(): void
    {
        $cleaner = new HtmlEntityCleaner();
        $numeric = 'Caf&#233;';
        $result = $cleaner->clean($numeric, 'UTF-8', []);

        $this->assertSame('Café', $result);
    }

    public function testCleanNoEntities(): void
    {
        $cleaner = new HtmlEntityCleaner();
        $plain = 'Café résumé';
        $result = $cleaner->clean($plain, 'UTF-8', []);

        $this->assertNull($result);
    }

    public function testCleanWithDifferentEncoding(): void
    {
        $cleaner = new HtmlEntityCleaner();
        $encoded = 'Caf&eacute;';
        $result = $cleaner->clean($encoded, 'ISO-8859-1', []);

        $this->assertIsString($result);
    }
}
