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

use Ducks\Component\EncodingRepair\CharsetHelper;
use Ducks\Component\EncodingRepair\Transcoder\TranscoderInterface;
use Ducks\Component\EncodingRepair\Detector\DetectorInterface;
use PHPUnit\Framework\TestCase;

final class CharsetHelperCoverageTest extends TestCase
{
    public function testRegisterTranscoderWithInterface(): void
    {
        $transcoder = $this->createMock(TranscoderInterface::class);
        $transcoder->method('getPriority')->willReturn(100);
        $transcoder->method('isAvailable')->willReturn(true);
        $transcoder->method('transcode')->willReturn('test');

        CharsetHelper::registerTranscoder($transcoder, 150);

        $result = CharsetHelper::toUtf8('test');
        $this->assertIsString($result);
    }

    public function testRegisterDetectorWithInterface(): void
    {
        $detector = $this->createMock(DetectorInterface::class);
        $detector->method('getPriority')->willReturn(100);
        $detector->method('isAvailable')->willReturn(true);
        $detector->method('detect')->willReturn('UTF-8');

        CharsetHelper::registerDetector($detector, 250);

        $encoding = CharsetHelper::detect('test');
        $this->assertSame('UTF-8', $encoding);
    }

    public function testToCharsetBatchWithArray(): void
    {
        $items = ['Café', 'Thé', 'Crème'];

        $result = CharsetHelper::toCharsetBatch($items, CharsetHelper::ENCODING_UTF8, CharsetHelper::ENCODING_UTF8);

        $this->assertIsArray($result);
        $this->assertCount(3, $result);
    }

    public function testToCharsetBatchWithIso(): void
    {
        $items = ['test1', 'test2', 'test3'];

        $result = CharsetHelper::toCharsetBatch($items, CharsetHelper::WINDOWS_1252, CharsetHelper::ENCODING_UTF8);

        $this->assertIsArray($result);
        $this->assertCount(3, $result);
    }

    public function testToCharsetBatchWithAutoDetection(): void
    {
        $items = ['test1', 'test2'];

        $result = CharsetHelper::toCharsetBatch($items, 'UTF-8', CharsetHelper::AUTO);

        $this->assertIsArray($result);
        $this->assertCount(2, $result);
    }
}
