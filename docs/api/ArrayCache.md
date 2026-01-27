# ArrayCache

Simple PSR-16 cache implementation using in-memory array.

## Overview

`ArrayCache` is a full-featured PSR-16 (Simple Cache) implementation with TTL support using an in-memory array for storage. It provides automatic expiration and is intended for external use when TTL is required.

For optimal performance without TTL overhead, use [InternalArrayCache](InternalArrayCache.md) instead (default in [CachedDetector](CachedDetector.md)).

**New in v1.2**: Full-featured PSR-16 implementation for external use.

## Class Synopsis

```php
namespace Ducks\Component\EncodingRepair\Cache;

final class ArrayCache implements CacheInterface
{
    public function get($key, $default = null);
    public function set($key, $value, $ttl = null): bool;
    public function delete($key): bool;
    public function clear(): bool;
    public function getMultiple($keys, $default = null): iterable;
    public function setMultiple($values, $ttl = null): bool;
    public function deleteMultiple($keys): bool;
    public function has($key): bool;
}
```

## Methods

### get

Fetch a value from the cache.

```php
public function get($key, $default = null)
```

**Parameters:**

- `$key` (string): Cache key
- `$default` (mixed): Default value if key not found

**Returns:** Cached value, or default if not found or expired.

### set

Persist data in the cache.

```php
public function set($key, $value, $ttl = null): bool
```

**Parameters:**

- `$key` (string): Cache key
- `$value` (mixed): Value to cache
- `$ttl` (null|int|DateInterval): Time to live (null = no expiration)

**Returns:** true on success, false on failure.

### delete

Delete an item from the cache.

```php
public function delete($key): bool
```

### clear

Wipe clean the entire cache.

```php
public function clear(): bool
```

### getMultiple

Obtain multiple cache items by their unique keys.

```php
public function getMultiple($keys, $default = null): iterable
```

### setMultiple

Persist multiple cache items.

```php
public function setMultiple($values, $ttl = null): bool
```

### deleteMultiple

Delete multiple cache items.

```php
public function deleteMultiple($keys): bool
```

### has

Determine if an item is present in the cache.

```php
public function has($key): bool
```

## Examples

### Basic Usage

```php
use Ducks\Component\EncodingRepair\Cache\ArrayCache;

$cache = new ArrayCache();

// Set value
$cache->set('key1', 'value1');

// Get value
echo $cache->get('key1'); // "value1"

// Get with default
echo $cache->get('missing', 'default'); // "default"
```

### TTL Support

```php
use Ducks\Component\EncodingRepair\Cache\ArrayCache;

$cache = new ArrayCache();

// Cache for 60 seconds
$cache->set('session_token', 'abc123', 60);

// Cache for 1 hour with DateInterval
$ttl = new \DateInterval('PT1H');
$cache->set('api_response', $data, $ttl);
```

### With CachedDetector (External Cache)

```php
use Ducks\Component\EncodingRepair\Cache\ArrayCache;
use Ducks\Component\EncodingRepair\Detector\CachedDetector;
use Ducks\Component\EncodingRepair\Detector\MbStringDetector;

// Use ArrayCache when TTL is needed
$psr16Cache = new ArrayCache();
$detector = new CachedDetector(new MbStringDetector(), $psr16Cache, 3600);

// Detection results cached with TTL
$encoding = $detector->detect('Café', []);
```

## Use Cases

- **External Cache with TTL**: When expiration is required
- **Development/Testing**: No need for Redis/Memcached setup
- **Unit Tests**: Easy to mock and verify cache behavior
- **Small Applications**: Simple caching without infrastructure

**Note**: For CachedDetector, [InternalArrayCache](InternalArrayCache.md) is used by default for better performance.

## Limitations

- **Not Persistent**: Data lost on script termination
- **Not Distributed**: Cannot share between processes/servers
- **Memory Only**: Limited by PHP memory_limit
- **Single Process**: Not suitable for multi-server deployments

## See Also

- [InternalArrayCache](InternalArrayCache.md)
- [CachedDetector](CachedDetector.md)
- [PSR-16 Simple Cache](https://www.php-fig.org/psr/psr-16/)
- [CharsetProcessor](CharsetProcessor.md)
