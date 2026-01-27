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
use Psr\SimpleCache\CacheInterface;

/**
 * Cached detector decorator with PSR-16 support.
 *
 * Uses InternalArrayCache by default for optimal performance.
 * Supports any PSR-16 cache implementation (Redis, Memcached, APCu, etc.).
 *
 * @final
 *
 * @psalm-api
 */
final class CachedDetector implements DetectorInterface
{
    private const HASH = 'sha256';

    private const DEFAULT_TTL = 3600;

    /**
     * @var DetectorInterface
     */
    private DetectorInterface $detector;

    /**
     * @var CacheInterface
     */
    private CacheInterface $cache;

    /**
     * @var int
     */
    private int $ttl;

    /**
     * @param DetectorInterface $detector Wrapped detector
     * @param CacheInterface|null $cache PSR-16 cache (default: InternalArrayCache)
     * @param int $ttl Cache TTL in seconds (default: 3600)
     */
    public function __construct(
        DetectorInterface $detector,
        ?CacheInterface $cache = null,
        int $ttl = self::DEFAULT_TTL
    ) {
        $this->detector = $detector;
        $this->cache = $cache ?? new InternalArrayCache(1000);
        $this->ttl = $ttl;
    }

    /**
     * @inheritDoc
     */
    public function detect(string $string, ?array $options = null): ?string
    {
        $key = $this->generateKey($string);

        /** @var mixed $cached */
        $cached = $this->cache->get($key);
        if (\is_string($cached)) {
            return $cached;
        }

        $result = $this->detector->detect($string, $options);

        if (\is_string($result)) {
            $this->cache->set($key, $result, $this->ttl);
            return $result;
        }

        return null;
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
     * Clear cache entries.
     *
     * @return void
     *
     * @psalm-api
     */
    public function clearCache(): void
    {
        $this->cache->clear();
    }

    /**
     * Get cache statistics.
     *
     * @return array{size: int, maxSize: int, class: string}
     *
     * @psalm-api
     */
    public function getCacheStats(): array
    {
        $size = 0;
        $maxSize = 1000;

        if ($this->cache instanceof InternalArrayCache) {
            $size = $this->cache->getSize();
            $maxSize = $this->cache->getMaxSize();
        }

        return [
            'size' => $size,
            'maxSize' => $maxSize,
            'class' => \get_class($this->cache),
        ];
    }

    /**
     * Generate cache key from string.
     *
     * @param string $string Input string
     *
     * @return string Cache key
     */
    private function generateKey(string $string): string
    {
        return 'encoding_detect_' . \hash(self::HASH, $string);
    }
}
