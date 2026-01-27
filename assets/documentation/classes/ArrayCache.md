# [The ArrayCache class](#the-arraycache-class)

(PHP 7 >= 7.4.0, PHP 8)

## [Introduction](#introduction)

ArrayCache is a full-featured PSR-16 (Simple Cache) implementation with TTL support using an
in-memory array for storage. It provides automatic expiration and is intended for external use
when TTL is required.

For optimal performance without TTL overhead, use [InternalArrayCache](InternalArrayCache.md) instead
(default in [CachedDetector](CachedDetector.md)).

**Architecture**: Implements PSR-16 CacheInterface with TTL and expiration support.

**New in v1.2**: Full-featured PSR-16 implementation for external use.

## [Class synopsis](#class-synopsis)

```php
final class ArrayCache implements CacheInterface {
    /* Methods */
    public get($key, $default = null)
    
    public set($key, $value, $ttl = null): bool
    
    public delete($key): bool
    
    public clear(): bool
    
    public getMultiple($keys, $default = null): iterable
    
    public setMultiple($values, $ttl = null): bool
    
    public deleteMultiple($keys): bool
    
    public has($key): bool
}
```

## [Features](#features)

- **PSR-16 Compliant**: Full implementation of Simple Cache interface
- **TTL Support**: Automatic expiration with int or DateInterval
- **In-Memory**: Fast array-based storage (no I/O)
- **Zero Dependencies**: No external cache server required
- **PHP 7.4+ Compatible**: No union types for backward compatibility
- **Lightweight**: Minimal memory footprint
- **Thread-Safe**: Each instance is isolated

## [Use Cases](#use-cases)

- **External Cache with TTL**: When expiration is required
- **Development/Testing**: No need for Redis/Memcached setup
- **Unit Tests**: Easy to mock and verify cache behavior
- **Small Applications**: Simple caching without infrastructure
- **CI/CD Pipelines**: No external dependencies

**Note**: For CachedDetector, [InternalArrayCache](InternalArrayCache.md) is used by default for better performance.

## [Examples](#examples)

### Example #1 Basic usage

```php
<?php

use Ducks\Component\EncodingRepair\Cache\ArrayCache;

$cache = new ArrayCache();

// Set value with default TTL (no expiration)
$cache->set('key1', 'value1');

// Get value
echo $cache->get('key1'); // "value1"

// Get with default
echo $cache->get('missing', 'default'); // "default"

// Check existence
var_dump($cache->has('key1')); // bool(true)

// Delete
$cache->delete('key1');
var_dump($cache->has('key1')); // bool(false)
```

### Example #2 TTL with integer seconds

```php
<?php

use Ducks\Component\EncodingRepair\Cache\ArrayCache;

$cache = new ArrayCache();

// Cache for 60 seconds
$cache->set('session_token', 'abc123', 60);

// Immediately available
echo $cache->get('session_token'); // "abc123"

// After 61 seconds (simulated)
sleep(61);
echo $cache->get('session_token', 'expired'); // "expired"
```

### Example #3 TTL with DateInterval

```php
<?php

use Ducks\Component\EncodingRepair\Cache\ArrayCache;

$cache = new ArrayCache();

// Cache for 1 hour
$ttl = new \DateInterval('PT1H');
$cache->set('api_response', $data, $ttl);

// Cache for 1 day
$ttl = new \DateInterval('P1D');
$cache->set('daily_stats', $stats, $ttl);
```

### Example #4 Multiple operations

```php
<?php

use Ducks\Component\EncodingRepair\Cache\ArrayCache;

$cache = new ArrayCache();

// Set multiple
$cache->setMultiple([
    'user:1' => ['name' => 'Alice'],
    'user:2' => ['name' => 'Bob'],
], 300);

// Get multiple
$users = $cache->getMultiple(['user:1', 'user:2', 'user:3'], null);
// ['user:1' => [...], 'user:2' => [...], 'user:3' => null]

// Delete multiple
$cache->deleteMultiple(['user:1', 'user:2']);
```

### Example #5 With CachedDetector (External Cache)

```php
<?php

use Ducks\Component\EncodingRepair\Cache\ArrayCache;
use Ducks\Component\EncodingRepair\Detector\CachedDetector;
use Ducks\Component\EncodingRepair\Detector\MbStringDetector;

// Use ArrayCache when TTL is needed
$psr16Cache = new ArrayCache();
$detector = new CachedDetector(new MbStringDetector(), $psr16Cache, 3600);

// Detection results cached with TTL
$encoding = $detector->detect('Café', []);
```

## [Methods](#methods)

### get

Fetch a value from the cache.

```php
public function get($key, $default = null)
```

**Parameters:**

- **key** (string): Cache key
- **default** (mixed): Default value if key not found

**Return Values:**

Returns cached value, or default if not found or expired.

### set

Persist data in the cache.

```php
public function set($key, $value, $ttl = null): bool
```

**Parameters:**

- **key** (string): Cache key
- **value** (mixed): Value to cache
- **ttl** (null|int|DateInterval): Time to live (null = no expiration)

**Return Values:**

Returns true on success, false on failure.

### delete

Delete an item from the cache.

```php
public function delete($key): bool
```

**Parameters:**

- **key** (string): Cache key

**Return Values:**

Returns true on success, false on failure.

### clear

Wipe clean the entire cache.

```php
public function clear(): bool
```

**Return Values:**

Returns true on success, false on failure.

### getMultiple

Obtain multiple cache items by their unique keys.

```php
public function getMultiple($keys, $default = null): iterable
```

**Parameters:**

- **keys** (iterable): List of keys
- **default** (mixed): Default value for missing keys

**Return Values:**

Returns iterable of key => value pairs.

### setMultiple

Persist multiple cache items.

```php
public function setMultiple($values, $ttl = null): bool
```

**Parameters:**

- **values** (iterable): Key => value pairs
- **ttl** (null|int|DateInterval): Time to live

**Return Values:**

Returns true on success, false on failure.

### deleteMultiple

Delete multiple cache items.

```php
public function deleteMultiple($keys): bool
```

**Parameters:**

- **keys** (iterable): List of keys

**Return Values:**

Returns true on success, false on failure.

### has

Determine if an item is present in the cache.

```php
public function has($key): bool
```

**Parameters:**

- **key** (string): Cache key

**Return Values:**

Returns true if key exists and not expired, false otherwise.

## [Performance](#performance)

### Characteristics

- **Get**: O(1) - Direct array access
- **Set**: O(1) - Direct array assignment
- **Memory**: ~50-100 bytes per entry (depends on value size)
- **Expiration Check**: O(1) - Timestamp comparison

### Limitations

- **No Persistence**: Data lost when process ends
- **No Sharing**: Each process has isolated cache
- **Memory Bound**: Large caches consume RAM
- **No Eviction**: No automatic cleanup of expired entries (lazy deletion)

## [Best Practices](#best-practices)

1. **Use for Testing**: Perfect for unit tests (no external dependencies)
2. **Small Datasets**: Keep cache size reasonable (< 10,000 entries)
3. **Short TTLs**: Use TTL to prevent memory bloat
4. **Development Only**: Use Redis/Memcached in production
5. **Clear Regularly**: Call clear() between test cases

## [Limitations](#limitations)

- **Not Persistent**: Data lost on script termination
- **Not Distributed**: Cannot share between processes/servers
- **No Eviction Policy**: No LRU/LFU automatic cleanup
- **Memory Only**: Limited by PHP memory_limit
- **Single Process**: Not suitable for multi-server deployments

## [Thread Safety](#thread-safety)

ArrayCache is **not thread-safe** across processes. Each process has its own isolated instance.

## [See Also](#see-also)

- [InternalArrayCache](InternalArrayCache.md) — Optimized cache without TTL (default for CachedDetector)
- [CachedDetector](CachedDetector.md) — Detector with cache support
- [PSR-16 Simple Cache](https://www.php-fig.org/psr/psr-16/) — PSR-16 specification
- [CharsetProcessor](CharsetProcessor.md) — Service using cached detection
