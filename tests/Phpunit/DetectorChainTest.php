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

use Ducks\Component\EncodingRepair\Detector\DetectorChain;
use Ducks\Component\EncodingRepair\Detector\DetectorInterface;
use PHPUnit\Framework\TestCase;

final class DetectorChainTest extends TestCase
{
    public function testRegisterAndDetect(): void
    {
        $chain = new DetectorChain();

        $detector = $this->createMock(DetectorInterface::class);
        $detector->method('isAvailable')->willReturn(true);
        $detector->method('getPriority')->willReturn(100);
        $detector->method('detect')->willReturn('UTF-8');

        $chain->register($detector);

        $result = $chain->detect('test', []);

        $this->assertSame('UTF-8', $result);
    }

    public function testRegisterWithCustomPriority(): void
    {
        $chain = new DetectorChain();

        $lowPriority = $this->createMock(DetectorInterface::class);
        $lowPriority->method('isAvailable')->willReturn(true);
        $lowPriority->method('getPriority')->willReturn(10);
        $lowPriority->method('detect')->willReturn('ISO-8859-1');

        $highPriority = $this->createMock(DetectorInterface::class);
        $highPriority->method('isAvailable')->willReturn(true);
        $highPriority->method('getPriority')->willReturn(50);
        $highPriority->method('detect')->willReturn('UTF-8');

        $chain->register($lowPriority);
        $chain->register($highPriority, 200);

        $result = $chain->detect('test', []);

        $this->assertSame('UTF-8', $result);
    }

    public function testDetectFallsBackToNextDetector(): void
    {
        $chain = new DetectorChain();

        $first = $this->createMock(DetectorInterface::class);
        $first->method('isAvailable')->willReturn(true);
        $first->method('getPriority')->willReturn(100);
        $first->method('detect')->willReturn(null);

        $second = $this->createMock(DetectorInterface::class);
        $second->method('isAvailable')->willReturn(true);
        $second->method('getPriority')->willReturn(50);
        $second->method('detect')->willReturn('ISO-8859-1');

        $chain->register($first);
        $chain->register($second);

        $result = $chain->detect('test', []);

        $this->assertSame('ISO-8859-1', $result);
    }

    public function testUnregisterRemovesDetector(): void
    {
        $chain = new DetectorChain();

        $detector = $this->createMock(DetectorInterface::class);
        $detector->method('isAvailable')->willReturn(true);
        $detector->method('getPriority')->willReturn(100);
        $detector->method('detect')->willReturn('UTF-8');

        $chain->register($detector);
        $chain->unregister($detector);

        $result = $chain->detect('test', []);

        $this->assertNull($result);
    }

    public function testMultipleRegistrationsWithDifferentPriorities(): void
    {
        $chain = new DetectorChain();

        $results = [];

        for ($i = 1; $i <= 3; $i++) {
            $detector = $this->createMock(DetectorInterface::class);
            $detector->method('isAvailable')->willReturn(true);
            $detector->method('getPriority')->willReturn($i * 10);
            $detector->method('detect')->willReturnCallback(
                static function () use ($i, &$results) {
                    $results[] = $i;
                    return null;
                }
            );

            $chain->register($detector);
        }

        $chain->detect('test', []);

        $this->assertSame([3, 2, 1], $results);
    }

    public function testDetectWithOptions(): void
    {
        $chain = new DetectorChain();

        $detector = $this->createMock(DetectorInterface::class);
        $detector->method('isAvailable')->willReturn(true);
        $detector->method('getPriority')->willReturn(100);
        $detector->expects($this->once())
            ->method('detect')
            ->with('test', ['encodings' => ['UTF-8', 'ISO-8859-1']])
            ->willReturn('UTF-8');

        $chain->register($detector);

        $result = $chain->detect('test', ['encodings' => ['UTF-8', 'ISO-8859-1']]);

        $this->assertSame('UTF-8', $result);
    }
}
