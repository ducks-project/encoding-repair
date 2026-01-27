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

namespace Ducks\Component\EncodingRepair\Traits;

use Ducks\Component\EncodingRepair\Cache\InternalArrayCache;
use Ducks\Component\EncodingRepair\Detector\DetectionCacheableInterface;
use Psr\SimpleCache\CacheInterface;

/**
 * Trait for detection result caching with PSR-16 support.
 *
 * Provides common caching functionality for detectors.
 *
 * Classes using this trait should implement DetectionCacheableInterface.
 *
 * @see DetectionCacheableInterface
 */
trait DetectionCacheTrait
{
    /**
     * @var CacheInterface|null
     */
    private ?CacheInterface $detectionCache = null;

    /**
     * @var int
     */
    private int $cacheTtl = DetectionCacheableInterface::DEFAULT_CACHE_TTL;

    /**
     * @var bool
     */
    private bool $detectionCacheEnabled = false;

    /**
     * Check if cache is enabled.
     *
     * @return bool
     */
    public function isCacheEnabled(): bool
    {
        return $this->detectionCacheEnabled;
    }

    /**
     * Get detection cache instance (lazy initialization).
     *
     * @return CacheInterface
     */
    protected function getDetectionCache(): CacheInterface
    {
        if (null === $this->detectionCache) {
            $this->detectionCache = new InternalArrayCache(1000);
        }

        return $this->detectionCache;
    }

    /**
     * Get cached detection result.
     *
     * @param string $string String to check in cache
     *
     * @return string|null Cached result or null if not found
     */
    protected function getCachedDetection(string $string): ?string
    {
        if (!$this->isCacheEnabled()) {
            return null;
        }

        $key = $this->generateCacheKey($string);
        /** @var mixed $cached */
        $cached = $this->getDetectionCache()->get($key);

        return \is_string($cached) ? $cached : null;
    }

    /**
     * Store detection result in cache.
     *
     * @param string $string String that was detected
     * @param string $result Detection result to cache
     *
     * @return void
     */
    protected function setCachedDetection(string $string, string $result): void
    {
        if (!$this->isCacheEnabled()) {
            return;
        }

        $key = $this->generateCacheKey($string);
        $this->getDetectionCache()->set($key, $result, $this->cacheTtl);
    }

    /**
     * Enable caching.
     *
     * @param CacheInterface|null $cache PSR-16 cache (default: InternalArrayCache)
     * @param int $ttl Cache TTL in seconds
     *
     * @return DetectionCacheableInterface&static
     */
    public function enableCache(
        ?CacheInterface $cache = null,
        int $ttl = DetectionCacheableInterface::DEFAULT_CACHE_TTL
    ): DetectionCacheableInterface {
        $this->detectionCache = $cache;
        $this->cacheTtl = $ttl;
        $this->detectionCacheEnabled = true;

        return $this;
    }

    /**
     * Disable caching.
     *
     * @return DetectionCacheableInterface&static
     */
    public function disableCache(): DetectionCacheableInterface
    {
        $this->detectionCacheEnabled = false;
        $this->detectionCache = null;

        return $this;
    }

    /**
     * Clear cache.
     *
     * @return DetectionCacheableInterface&static
     */
    public function clearCache(): DetectionCacheableInterface
    {
        if ($this->isCacheEnabled()) {
            $this->getDetectionCache()->clear();
        }

        return $this;
    }

    /**
     * Generate cache key from string.
     *
     * @param string $string Input string
     *
     * @return string Cache key
     */
    private function generateCacheKey(string $string): string
    {
        return DetectionCacheableInterface::CACHE_KEY_PREFIX
            . \hash(DetectionCacheableInterface::CACHE_HASH_ALGO, $string);
    }
}
