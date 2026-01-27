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

use Psr\SimpleCache\CacheInterface;

/**
 * Interface for objects supporting detection result caching.
 *
 * Provides fluent API for cache management.
 */
interface DetectionCacheableInterface
{
    /**
     * Default cache TTL in seconds.
     */
    public const DEFAULT_CACHE_TTL = 3600;

    /**
     * Cache key prefix.
     */
    public const CACHE_KEY_PREFIX = 'encoding_detect_';

    /**
     * Hash algorithm for cache keys.
     */
    public const CACHE_HASH_ALGO = 'sha256';
    /**
     * Check if cache is enabled.
     *
     * @return bool
     */
    public function isCacheEnabled(): bool;

    /**
     * Enable caching.
     *
     * @param CacheInterface|null $cache PSR-16 cache (default: InternalArrayCache)
     * @param int $ttl Cache TTL in seconds
     *
     * @return $this
     */
    public function enableCache(?CacheInterface $cache = null, int $ttl = self::DEFAULT_CACHE_TTL);

    /**
     * Disable caching.
     *
     * @return $this
     */
    public function disableCache();

    /**
     * Clear cache.
     *
     * @return $this
     */
    public function clearCache();
}
