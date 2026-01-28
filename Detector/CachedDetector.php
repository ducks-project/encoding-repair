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

namespace Ducks\Component\EncodingRepair\Detector;

use Ducks\Component\EncodingRepair\Cache\InternalArrayCache;
use Ducks\Component\EncodingRepair\Traits\DetectionCacheTrait;
use Psr\SimpleCache\CacheInterface;

/**
 * Cached detector decorator with PSR-16 support.
 *
 * Uses InternalArrayCache by default for optimal performance.
 * Supports any PSR-16 cache implementation (Redis, Memcached, APCu, etc.).s
 *
 * Wraps a single detector to cache its results. Useful for:
 * - Caching expensive detectors (e.g., FileInfoDetector)
 * - Per-detector cache configuration
 * - Fine-grained cache control
 *
 * For caching the entire detector chain, use DetectorChain::enableCache() instead.
 *
 * @final
 *
 * @psalm-api
 */
final class CachedDetector implements DetectorInterface, DetectionCacheableInterface
{
    use DetectionCacheTrait;

    /**
     * @var DetectorInterface
     */
    private DetectorInterface $detector;

    /**
     * @param DetectorInterface $detector Wrapped detector
     * @param CacheInterface|null $cache PSR-16 cache (default: InternalArrayCache)
     * @param int $ttl Cache TTL in seconds (default: 3600)
     */
    public function __construct(
        DetectorInterface $detector,
        ?CacheInterface $cache = null,
        int $ttl = 3600
    ) {
        $this->detector = $detector;
        $this->enableCache($cache, $ttl);
    }

    /**
     * @inheritDoc
     */
    public function detect(string $string, ?array $options = null): ?string
    {
        // Check cache
        $cached = $this->getCachedDetection($string);
        if (null !== $cached) {
            return $cached;
        }

        // Execute detector
        $result = $this->detector->detect($string, $options);

        // Store in cache
        if (null !== $result) {
            $this->setCachedDetection($string, $result);
        }

        return $result;
    }

    /**
     * @inheritDoc
     */
    public function getPriority(): int
    {
        return 200;
    }

    /**
     * @inheritDoc
     */
    public function isAvailable(): bool
    {
        return $this->detector->isAvailable();
    }

    /**
     * Get cache statistics.
     *
     * @return array{size: int, maxSize: int, class: class-string|class-string<CacheInterface>|false}
     *
     * @psalm-api
     */
    public function getCacheStats(): array
    {
        $size = 0;
        $maxSize = 1000;

        $detectionCache = $this->getDetectionCache();

        if ($detectionCache instanceof InternalArrayCache) {
            $size = $detectionCache->getSize();
            $maxSize = $detectionCache->getMaxSize();
        }

        return [
            'size' => $size,
            'maxSize' => $maxSize,
            'class' => \get_class($detectionCache),
        ];
    }
}
