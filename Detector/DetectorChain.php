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

use Ducks\Component\EncodingRepair\Traits\ChainOfResponsibilityTrait;
use Ducks\Component\EncodingRepair\Traits\DetectionCacheTrait;
use Psr\SimpleCache\CacheInterface;

/**
 * Chain of Responsibility for detectors with priority management and optional caching.
 *
 * @final
 */
final class DetectorChain implements DetectionCacheableInterface
{
    /**
     * @use ChainOfResponsibilityTrait<DetectorInterface>
     */
    use ChainOfResponsibilityTrait {
        ChainOfResponsibilityTrait::register as chainRegister;
        ChainOfResponsibilityTrait::unregister as chainUnregister;
    }
    use DetectionCacheTrait;

    /**
     * @param CacheInterface|null $cache Optional PSR-16 cache
     * @param int $ttl Cache TTL in seconds
     */
    public function __construct(
        ?CacheInterface $cache = null,
        int $ttl = 3600
    ) {
        if (null !== $cache) {
            $this->enableCache($cache, $ttl);
        }
    }

    /**
     * Register a detector with optional priority override.
     *
     * @param DetectorInterface $detector Detector instance
     * @param int|null $priority Priority override (null = use detector's default)
     *
     * @return void
     */
    public function register(DetectorInterface $detector, ?int $priority = null): void
    {
        $this->chainRegister($detector, $priority);
    }

    /**
     * Unregister a detector from the chain.
     *
     * @param DetectorInterface $detector Detector instance to remove
     *
     * @return void
     */
    public function unregister(DetectorInterface $detector): void
    {
        $this->chainUnregister($detector);
    }

    /**
     * Execute detection using chain of responsibility with optional caching.
     *
     * @param string $string String to analyze
     * @param array<string, mixed> $options Detection options
     *
     * @return string|null Detected encoding or null if all failed
     */
    public function detect(string $string, array $options): ?string
    {
        // Check cache
        $cached = $this->getCachedDetection($string);
        if (null !== $cached) {
            return $cached;
        }

        // Execute chain
        $result = $this->executeChain($string, $options);

        // Store in cache
        if (null !== $result) {
            $this->setCachedDetection($string, $result);
        }

        return $result;
    }

    /**
     * Execute the detector chain without caching.
     *
     * @param string $string String to analyze
     * @param array<string, mixed> $options Detection options
     *
     * @return string|null Detected encoding or null if all failed
     */
    private function executeChain(string $string, array $options): ?string
    {
        // Clone the queue to avoid consuming it
        $queue = clone $this->getSplPriorityQueue();

        foreach ($queue as $detector) {
            /** @disregard P1013 Undefined method */
            if (!$detector->isAvailable()) {
                // @codeCoverageIgnoreStart
                continue;
                // @codeCoverageIgnoreEnd
            }

            /** @disregard P1013 Undefined method */
            $result = $detector->detect($string, $options);

            if (null !== $result) {
                return $result;
            }
        }

        // @codeCoverageIgnoreStart
        return null;
        // @codeCoverageIgnoreEnd
    }
}
