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

use Ducks\Component\EncodingRepair\Transcoder\TranscoderChain;
use Ducks\Component\EncodingRepair\Transcoder\TranscoderInterface;
use PHPUnit\Framework\TestCase;

final class TranscoderChainTest extends TestCase
{
    public function testRegisterAndTranscode(): void
    {
        $chain = new TranscoderChain();

        $transcoder = $this->createMock(TranscoderInterface::class);
        $transcoder->method('isAvailable')->willReturn(true);
        $transcoder->method('getPriority')->willReturn(100);
        $transcoder->method('transcode')->willReturn('converted');

        $chain->register($transcoder);

        $result = $chain->transcode('test', 'UTF-8', 'ISO-8859-1', []);

        $this->assertSame('converted', $result);
    }

    public function testRegisterWithCustomPriority(): void
    {
        $chain = new TranscoderChain();

        $lowPriority = $this->createMock(TranscoderInterface::class);
        $lowPriority->method('isAvailable')->willReturn(true);
        $lowPriority->method('getPriority')->willReturn(10);
        $lowPriority->method('transcode')->willReturn('low');

        $highPriority = $this->createMock(TranscoderInterface::class);
        $highPriority->method('isAvailable')->willReturn(true);
        $highPriority->method('getPriority')->willReturn(50);
        $highPriority->method('transcode')->willReturn('high');

        $chain->register($lowPriority);
        $chain->register($highPriority, 200);

        $result = $chain->transcode('test', 'UTF-8', 'ISO-8859-1', []);

        $this->assertSame('high', $result);
    }

    public function testTranscodeFallsBackToNextTranscoder(): void
    {
        $chain = new TranscoderChain();

        $first = $this->createMock(TranscoderInterface::class);
        $first->method('isAvailable')->willReturn(true);
        $first->method('getPriority')->willReturn(100);
        $first->method('transcode')->willReturn(null);

        $second = $this->createMock(TranscoderInterface::class);
        $second->method('isAvailable')->willReturn(true);
        $second->method('getPriority')->willReturn(50);
        $second->method('transcode')->willReturn('fallback');

        $chain->register($first);
        $chain->register($second);

        $result = $chain->transcode('test', 'UTF-8', 'ISO-8859-1', []);

        $this->assertSame('fallback', $result);
    }

    public function testUnregisterRemovesTranscoder(): void
    {
        $chain = new TranscoderChain();

        $transcoder = $this->createMock(TranscoderInterface::class);
        $transcoder->method('isAvailable')->willReturn(true);
        $transcoder->method('getPriority')->willReturn(100);
        $transcoder->method('transcode')->willReturn('converted');

        $chain->register($transcoder);
        $chain->unregister($transcoder);

        $result = $chain->transcode('test', 'UTF-8', 'ISO-8859-1', []);

        $this->assertNull($result);
    }

    public function testMultipleRegistrationsWithDifferentPriorities(): void
    {
        $chain = new TranscoderChain();

        $results = [];

        for ($i = 1; $i <= 3; $i++) {
            $transcoder = $this->createMock(TranscoderInterface::class);
            $transcoder->method('isAvailable')->willReturn(true);
            $transcoder->method('getPriority')->willReturn($i * 10);
            $transcoder->method('transcode')->willReturnCallback(
                static function () use ($i, &$results) {
                    $results[] = $i;
                    return null;
                }
            );

            $chain->register($transcoder);
        }

        $chain->transcode('test', 'UTF-8', 'ISO-8859-1', []);

        $this->assertSame([3, 2, 1], $results);
    }
}
