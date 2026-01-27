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
 * Simple array-based PSR-16 cache implementation.
 *
 * @final
 *
 * @psalm-api
 */
final class ArrayCache implements CacheInterface
{
    /**
     * @var array<string, array{value: mixed, expiry: int|null}>
     */
    private array $storage = [];

    /**
     * @inheritDoc
     *
     * @param string $key
     * @param mixed $default
     *
     * @return mixed
     */
    public function get($key, $default = null)
    {
        if (!isset($this->storage[$key])) {
            return $default;
        }

        $item = $this->storage[$key];

        if (null !== $item['expiry'] && $item['expiry'] < \time()) {
            unset($this->storage[$key]);

            return $default;
        }

        return $item['value'];
    }

    /**
     * @inheritDoc
     *
     * @param string $key
     * @param mixed $value
     * @param null|int|\DateInterval $ttl
     */
    public function set($key, $value, $ttl = null): bool
    {
        $expiry = $this->calculateExpiry($ttl);
        $this->storage[$key] = ['value' => $value, 'expiry' => $expiry];

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
     * Cache keys that do not exist or are stale will have $default as value.
     */
    public function getMultiple($keys, $default = null)
    {
        /** @var array<string, mixed> $result */
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
        return null !== $this->get($key);
    }

    /**
     * Calculate expiry timestamp from TTL.
     *
     * @param null|int|\DateInterval $ttl TTL value
     *
     * @return int|null Expiry timestamp or null for no expiry
     */
    private function calculateExpiry($ttl): ?int
    {
        if (null === $ttl) {
            return null;
        }

        if ($ttl instanceof \DateInterval) {
            return (new \DateTime())->add($ttl)->getTimestamp();
        }

        return \time() + $ttl;
    }
}
