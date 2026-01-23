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

use Ducks\Component\EncodingRepair\Detector\CachedDetector;
use Ducks\Component\EncodingRepair\Detector\DetectorInterface;
use Ducks\Component\EncodingRepair\Detector\MbStringDetector;
use PHPUnit\Framework\TestCase;

final class CachedDetectorTest extends TestCase
{
    public function testDetectCachesResult(): void
    {
        $callCount = 0;
        $mockDetector = $this->createMockDetector('UTF-8', $callCount);

        $cached = new CachedDetector($mockDetector);

        $result1 = $cached->detect('test string', []);
        $result2 = $cached->detect('test string', []);

        $this->assertSame('UTF-8', $result1);
        $this->assertSame('UTF-8', $result2);
        $this->assertSame(1, $callCount);
    }

    public function testDetectDifferentStringsCallsDetector(): void
    {
        $callCount = 0;
        $mockDetector = $this->createMockDetector('UTF-8', $callCount);

        $cached = new CachedDetector($mockDetector);

        $cached->detect('string1', []);
        $cached->detect('string2', []);

        $this->assertSame(2, $callCount);
    }

    public function testDetectRespectsMaxSize(): void
    {
        $mockDetector = $this->createMockDetector('UTF-8');
        $cached = new CachedDetector($mockDetector, 2);

        $cached->detect('string1', []);
        $cached->detect('string2', []);
        $cached->detect('string3', []);

        $stats = $cached->getCacheStats();
        $this->assertSame(2, $stats['size']);
        $this->assertSame(2, $stats['maxSize']);
    }

    public function testDetectHandlesNullResult(): void
    {
        $mockDetector = $this->createMockDetector(null);
        $cached = new CachedDetector($mockDetector);

        $result = $cached->detect('test', []);

        $this->assertNull($result);
        $stats = $cached->getCacheStats();
        $this->assertSame(0, $stats['size']);
    }

    public function testGetPriorityReturns200(): void
    {
        $mockDetector = $this->createMockDetector('UTF-8');
        $cached = new CachedDetector($mockDetector);

        $this->assertSame(200, $cached->getPriority());
    }

    public function testIsAvailableDelegatesToWrappedDetector(): void
    {
        $mockDetector = $this->createMock(DetectorInterface::class);
        $mockDetector->method('isAvailable')->willReturn(false);

        $cached = new CachedDetector($mockDetector);

        $this->assertFalse($cached->isAvailable());
    }

    public function testClearCacheRemovesAllEntries(): void
    {
        $mockDetector = $this->createMockDetector('UTF-8');
        $cached = new CachedDetector($mockDetector);

        $cached->detect('string1', []);
        $cached->detect('string2', []);

        $this->assertSame(2, $cached->getCacheStats()['size']);

        $cached->clearCache();

        $this->assertSame(0, $cached->getCacheStats()['size']);
    }

    public function testGetCacheStatsReturnsCorrectData(): void
    {
        $mockDetector = $this->createMockDetector('UTF-8');
        $cached = new CachedDetector($mockDetector);

        $cached->detect('test', []);

        $stats = $cached->getCacheStats();

        $this->assertIsArray($stats);
        $this->assertArrayHasKey('size', $stats);
        $this->assertArrayHasKey('maxSize', $stats);
        $this->assertSame(1, $stats['size']);
        $this->assertSame(1000, $stats['maxSize']);
    }

    public function testIntegrationWithMbStringDetector(): void
    {
        $detector = new MbStringDetector();
        $cached = new CachedDetector($detector);

        $utf8String = 'Café résumé';
        $result1 = $cached->detect($utf8String, []);
        $result2 = $cached->detect($utf8String, []);

        $this->assertSame('UTF-8', $result1);
        $this->assertSame('UTF-8', $result2);
        $this->assertSame(1, $cached->getCacheStats()['size']);
    }

    public function testCacheKeyCollisionHandling(): void
    {
        $mockDetector = $this->createMockDetector('UTF-8');
        $cached = new CachedDetector($mockDetector);

        $string1 = 'test';
        $string2 = 'test';

        $cached->detect($string1, []);
        $cached->detect($string2, []);

        $this->assertSame(1, $cached->getCacheStats()['size']);
    }

    public function testDefaultMaxSize(): void
    {
        $mockDetector = $this->createMockDetector('UTF-8');
        $cached = new CachedDetector($mockDetector);

        $stats = $cached->getCacheStats();
        $this->assertSame(1000, $stats['maxSize']);
    }

    public function testCacheAfterClear(): void
    {
        $callCount = 0;
        $mockDetector = $this->createMockDetector('UTF-8', $callCount);
        $cached = new CachedDetector($mockDetector);

        $cached->detect('test', []);
        $this->assertSame(1, $callCount);

        $cached->clearCache();

        $cached->detect('test', []);
        $this->assertSame(2, $callCount);
    }

    /**
     * @param string|null $returnValue
     * @param int|null $callCount
     *
     * @return DetectorInterface
     */
    private function createMockDetector(?string $returnValue, ?int &$callCount = null): DetectorInterface
    {
        $mock = $this->createMock(DetectorInterface::class);
        $mock->method('isAvailable')->willReturn(true);
        $mock->method('getPriority')->willReturn(100);

        if (null !== $callCount) {
            $mock->method('detect')->willReturnCallback(
                /**
                 * @param array<string, mixed>|null $options
                 */
                static function (string $string, ?array $options = null) use ($returnValue, &$callCount): ?string {
                    $callCount++;

                    return $returnValue;
                }
            );
        } else {
            $mock->method('detect')->willReturn($returnValue);
        }

        return $mock;
    }
}
