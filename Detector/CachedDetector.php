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

/**
 * Cached detector decorator with LRU-like eviction.
 *
 * @final
 *
 * @psalm-api
 */
final class CachedDetector implements DetectorInterface
{
    private const MAX_SIZE = 1000;

    /**
     * @var DetectorInterface
     */
    private DetectorInterface $detector;

    /**
     * @var array<string, string>
     */
    private array $cache = [];

    /**
     * @param DetectorInterface $detector Wrapped detector
     */
    public function __construct(DetectorInterface $detector)
    {
        $this->detector = $detector;
    }

    /**
     * @inheritDoc
     */
    public function detect(string $string, ?array $options = null): ?string
    {
        /** @phan-var string|false $key */
        $key = \hash('xxh64', $string);

        if (false !== $key && isset($this->cache[$key])) {
            return $this->cache[$key];
        }

        $result = $this->detector->detect($string, $options);

        if (null !== $result) {
            if (\count($this->cache) >= self::MAX_SIZE) {
                // LRU eviction: remove oldest entry
                \array_shift($this->cache);
            }
            if (false !== $key) {
                $this->cache[$key] = $result;
            }
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
     * Clear cache entries.
     *
     * @return void
     *
     * @psalm-api
     */
    public function clearCache(): void
    {
        $this->cache = [];
    }

    /**
     * Get cache statistics.
     *
     * @return array{size: int, maxSize: int}
     *
     * @psalm-api
     */
    public function getCacheStats(): array
    {
        return [
            'size' => \count($this->cache),
            'maxSize' => self::MAX_SIZE,
        ];
    }
}
