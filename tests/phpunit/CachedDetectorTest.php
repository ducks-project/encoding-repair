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

use Ducks\Component\EncodingRepair\Cache\ArrayCache;
use Ducks\Component\EncodingRepair\Detector\CachedDetector;
use Ducks\Component\EncodingRepair\Detector\DetectorInterface;
use Ducks\Component\EncodingRepair\Detector\MbStringDetector;
use PHPUnit\Framework\TestCase;
use Psr\SimpleCache\CacheInterface;

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

    public function testGetCacheStatsReturnsCorrectData(): void
    {
        $mockDetector = $this->createMockDetector('UTF-8');
        $cached = new CachedDetector($mockDetector);

        $cached->detect('test', []);

        $stats = $cached->getCacheStats();

        $this->assertIsArray($stats);
        $this->assertArrayHasKey('size', $stats);
        $this->assertArrayHasKey('maxSize', $stats);
        $this->assertArrayHasKey('class', $stats);
        $this->assertSame(1, $stats['size']);
        $this->assertSame(1000, $stats['maxSize']);
        $this->assertSame('Ducks\\Component\\EncodingRepair\\Cache\\InternalArrayCache', $stats['class']);
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

    public function testCacheAfterClear(): void
    {
        $callCount = 0;
        $mockDetector = $this->createMockDetector('UTF-8', $callCount);
        $cached = new CachedDetector($mockDetector);

        $cached->detect('string1', []);
        $cached->detect('string2', []);
        $this->assertSame(2, $callCount);
        $this->assertSame(2, $cached->getCacheStats()['size']);

        $cached->clearCache();
        $this->assertSame(0, $cached->getCacheStats()['size']);

        $cached->detect('string1', []);
        $this->assertSame(3, $callCount);
    }

    public function testWithPsr16Cache(): void
    {
        $callCount = 0;
        $mockDetector = $this->createMockDetector('UTF-8', $callCount);
        $psr16Cache = new ArrayCache();
        $cached = new CachedDetector($mockDetector, $psr16Cache);

        $result1 = $cached->detect('test string', []);
        $result2 = $cached->detect('test string', []);

        $this->assertSame('UTF-8', $result1);
        $this->assertSame('UTF-8', $result2);
        $this->assertSame(1, $callCount);
        $this->assertSame('Ducks\\Component\\EncodingRepair\\Cache\\ArrayCache', $cached->getCacheStats()['class']);
    }

    public function testPsr16CacheClear(): void
    {
        $callCount = 0;
        $mockDetector = $this->createMockDetector('UTF-8', $callCount);
        $psr16Cache = new ArrayCache();
        $cached = new CachedDetector($mockDetector, $psr16Cache);

        $cached->detect('test', []);
        $this->assertSame(1, $callCount);

        $cached->clearCache();
        $cached->detect('test', []);
        $this->assertSame(2, $callCount);
    }

    public function testCustomTtl(): void
    {
        $mockDetector = $this->createMockDetector('UTF-8');
        $psr16Cache = new ArrayCache();
        $cached = new CachedDetector($mockDetector, $psr16Cache, 7200);

        $cached->detect('test', []);
        $this->assertSame('UTF-8', $cached->detect('test', []));
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
