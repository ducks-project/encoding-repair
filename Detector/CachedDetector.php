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
    /**
     * @var DetectorInterface
     */
    private DetectorInterface $detector;

    /**
     * @var array<string, string>
     */
    private array $cache = [];

    /**
     * @var int
     */
    private int $maxSize;

    /**
     * @param DetectorInterface $detector Wrapped detector
     * @param int $maxSize Maximum cache entries (default: 1000)
     */
    public function __construct(DetectorInterface $detector, int $maxSize = 1000)
    {
        $this->detector = $detector;
        $this->maxSize = $maxSize;
    }

    /**
     * @inheritDoc
     */
    public function detect(string $string, ?array $options = null): ?string
    {
        $key = \hash('sha256', $string);

        if (isset($this->cache[$key])) {
            return $this->cache[$key];
        }

        $result = $this->detector->detect($string, $options);

        if (null !== $result && \count($this->cache) < $this->maxSize) {
            $this->cache[$key] = $result;
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
            'maxSize' => $this->maxSize,
        ];
    }
}
