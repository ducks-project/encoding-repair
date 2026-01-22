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

namespace Ducks\Component\EncodingRepair\Tests\phpunit;

use Ducks\Component\EncodingRepair\Transcoder\IconvTranscoder;
use PHPUnit\Framework\TestCase;

final class IconvTranscoderTest extends TestCase
{
    public function testGetPriority(): void
    {
        $transcoder = new IconvTranscoder();
        $this->assertSame(50, $transcoder->getPriority());
    }

    public function testIsAvailable(): void
    {
        $transcoder = new IconvTranscoder();
        $this->assertSame(extension_loaded('iconv'), $transcoder->isAvailable());
    }

    public function testTranscodeUtf8ToIso(): void
    {
        if (!extension_loaded('iconv')) {
            $this->markTestSkipped('iconv extension not available');
        }

        $transcoder = new IconvTranscoder();
        $result = $transcoder->transcode('Café', 'ISO-8859-1', 'UTF-8', []);
        
        $this->assertIsString($result);
    }

    public function testTranscodeWithTranslit(): void
    {
        if (!extension_loaded('iconv')) {
            $this->markTestSkipped('iconv extension not available');
        }

        $transcoder = new IconvTranscoder();
        $result = $transcoder->transcode('Café', 'ASCII', 'UTF-8', ['translit' => true]);
        
        $this->assertIsString($result);
    }

    public function testTranscodeReturnsNullWhenNotAvailable(): void
    {
        if (extension_loaded('iconv')) {
            $this->markTestSkipped('iconv extension is available');
        }

        $transcoder = new IconvTranscoder();
        $result = $transcoder->transcode('test', 'UTF-8', 'ISO-8859-1', []);
        
        $this->assertNull($result);
    }
}
