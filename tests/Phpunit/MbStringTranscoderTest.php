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

use Ducks\Component\EncodingRepair\Transcoder\MbStringTranscoder;
use PHPUnit\Framework\TestCase;

final class MbStringTranscoderTest extends TestCase
{
    public function testGetPriority(): void
    {
        $transcoder = new MbStringTranscoder();
        $this->assertSame(10, $transcoder->getPriority());
    }

    public function testIsAvailable(): void
    {
        $transcoder = new MbStringTranscoder();
        $this->assertTrue($transcoder->isAvailable());
    }

    public function testTranscodeUtf8ToIso(): void
    {
        $transcoder = new MbStringTranscoder();
        $result = $transcoder->transcode('Café', 'ISO-8859-1', 'UTF-8', []);

        $this->assertIsString($result);
        $this->assertNotEmpty($result);
    }

    public function testTranscodeIsoToUtf8(): void
    {
        $transcoder = new MbStringTranscoder();
        $iso = \mb_convert_encoding('Café', 'ISO-8859-1', 'UTF-8');
        $result = $transcoder->transcode((string) $iso, 'UTF-8', 'ISO-8859-1', []);

        $this->assertSame('Café', $result);
    }
}
