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

namespace Ducks\Component\EncodingRepair\Cache;

use Psr\SimpleCache\CacheInterface;

/**
 * Internal LRU cache without TTL for optimal performance.
 *
 * Lightweight PSR-16 implementation optimized for CachedDetector.
 * No TTL calculation, no expiry checks, pure O(1) operations.
 *
 * @final
 *
 * @psalm-api
 */
final class InternalArrayCache implements CacheInterface
{
    /**
     * @var array<string, mixed>
     */
    private array $storage = [];

    /**
     * @var int
     */
    private int $maxSize;

    /**
     * @param int $maxSize Maximum cache entries
     */
    public function __construct(int $maxSize = 1000)
    {
        $this->maxSize = $maxSize;
    }

    /**
     * @inheritDoc
     */
    public function get($key, $default = null)
    {
        return $this->storage[$key] ?? $default;
    }

    /**
     * @inheritDoc
     */
    public function set($key, $value, $ttl = null): bool
    {
        if (\count($this->storage) >= $this->maxSize) {
            \array_shift($this->storage);
        }
        $this->storage[$key] = $value;

        return true;
    }

    /**
     * @inheritDoc
     */
    public function delete($key): bool
    {
        unset($this->storage[$key]);

        return true;
    }

    /**
     * @inheritDoc
     */
    public function clear(): bool
    {
        $this->storage = [];

        return true;
    }

    /**
     * @inheritDoc
     *
     * @param iterable<mixed,mixed> $keys A list of keys that can obtained in a single operation.
     * @param mixed $default Default value to return for keys that do not exist.
     *
     * @return array<array-key, mixed> A list of key => value pairs.
     *                                 Cache keys that do not exist or are stale will have $default as value.
     */
    public function getMultiple($keys, $default = null): iterable
    {
        $result = [];

        /** @var string $key */
        foreach ($keys as $key) {
            /** @var object|resource|iterable<mixed,mixed>|string|float|int|bool|null $value */
            $value = $this->get($key, $default);

            $result[$key] = $value;
        }

        return $result;
    }

    /**
     * @inheritDoc
     *
     * @param iterable<mixed,mixed> $values A list of key => value pairs for a multiple-set operation.
     * @param null|int|\DateInterval $ttl Optional. The TTL value of this item. If no value is sent and
     *                                    the driver supports TTL then the library may set a default value
     *                                    for it or let the driver take care of that.
     */
    public function setMultiple($values, $ttl = null): bool
    {
        /**
         * @var mixed $key
         * @var mixed $value
         */
        foreach ($values as $key => $value) {
            $this->set((string) $key, $value, $ttl);
        }

        return true;
    }

    /**
     * @inheritDoc
     *
     * @param iterable<mixed,mixed> $keys A list of string-based keys to be deleted.
     */
    public function deleteMultiple($keys): bool
    {
        /** @var string $key */
        foreach ($keys as $key) {
            $this->delete($key);
        }

        return true;
    }

    /**
     * @inheritDoc
     */
    public function has($key): bool
    {
        return isset($this->storage[$key]);
    }

    /**
     * Get cache size.
     *
     * @return int Current number of entries
     */
    public function getSize(): int
    {
        return \count($this->storage);
    }

    /**
     * Get max size.
     *
     * @return int Maximum number of entries
     */
    public function getMaxSize(): int
    {
        return $this->maxSize;
    }
}
