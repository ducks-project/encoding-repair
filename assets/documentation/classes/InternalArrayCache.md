# [The InternalArrayCache class](#the-internalarraycache-class)

(PHP 7 >= 7.4.0, PHP 8)

## [Introduction](#introduction)

InternalArrayCache is an optimized PSR-16 (Simple Cache) implementation designed specifically for
[CachedDetector](CachedDetector.md). It provides pure O(1) operations without TTL overhead,
making it the fastest possible cache implementation for detection results.

**Architecture**: Lightweight PSR-16 implementation with LRU eviction and no expiration checks.

**New in v1.2**: Default cache for CachedDetector, optimized for performance.

## [Class synopsis](#class-synopsis)

```php
final class InternalArrayCache implements CacheInterface {
    /* Methods */
    public __construct(int $maxSize = 1000)
    
    public get($key, $default = null)
    
    public set($key, $value, $ttl = null): bool
    
    public delete($key): bool
    
    public clear(): bool
    
    public getMultiple($keys, $default = null): iterable
    
    public setMultiple($values, $ttl = null): bool
    
    public deleteMultiple($keys): bool
    
    public has($key): bool
    
    public getSize(): int
    
    public getMaxSize(): int
}
```

## [Features](#features)

- **Zero TTL Overhead**: No expiration calculation or checks
- **Pure O(1) Operations**: Direct array access without wrapping
- **LRU Eviction**: Simple array_shift() when max size reached
- **Minimal Memory**: ~50 bytes per entry (no expiry metadata)
- **PSR-16 Compliant**: Full interface implementation
- **Optimized for CachedDetector**: Default cache implementation
- **PHP 7.4+ Compatible**: No union types

## [Constructor](#constructor)

### __construct

```php
public function __construct(int $maxSize = 1000)
```

**Parameters:**

- `$maxSize` (int): Maximum cache entries (default: 1000)

## [Methods](#methods)

### get

Fetch a value from the cache.

```php
public function get($key, $default = null)
```

**Performance:** O(1) - Direct array access, no expiry check

### set

Persist data in the cache.

```php
public function set($key, $value, $ttl = null): bool
```

**Parameters:**

- `$ttl` (null|int|DateInterval): Ignored (no TTL support)

**Performance:** O(1) for normal case, O(n) when eviction needed

**Notes:**

- TTL parameter is ignored for performance
- LRU eviction with array_shift() when full

### getSize

Get current cache size.

```php
public function getSize(): int
```

**Returns:** Current number of entries

### getMaxSize

Get maximum cache size.

```php
public function getMaxSize(): int
```

**Returns:** Maximum number of entries

## [Examples](#examples)

### Basic Usage (Default in CachedDetector)

```php
use Ducks\Component\EncodingRepair\Detector\CachedDetector;
use Ducks\Component\EncodingRepair\Detector\MbStringDetector;

// InternalArrayCache is used automatically
$detector = new CachedDetector(new MbStringDetector());

$encoding = $detector->detect('Café', []);

$stats = $detector->getCacheStats();
// ['size' => 1, 'maxSize' => 1000, 'class' => 'Ducks\...\InternalArrayCache']
```

### Manual Instantiation

```php
use Ducks\Component\EncodingRepair\Cache\InternalArrayCache;

$cache = new InternalArrayCache(500);  // Custom max size

$cache->set('key1', 'value1');
echo $cache->get('key1'); // "value1"

echo $cache->getSize(); // 1
echo $cache->getMaxSize(); // 500
```

### LRU Eviction

```php
use Ducks\Component\EncodingRepair\Cache\InternalArrayCache;

$cache = new InternalArrayCache(3);  // Max 3 entries

$cache->set('key1', 'value1');
$cache->set('key2', 'value2');
$cache->set('key3', 'value3');
echo $cache->getSize(); // 3

$cache->set('key4', 'value4');  // Evicts key1
echo $cache->getSize(); // 3
echo $cache->get('key1'); // null (evicted)
echo $cache->get('key4'); // "value4"
```

## [Performance](#performance)

### Characteristics

- **Get**: O(1) - Direct array access
- **Set**: O(1) average, O(n) when evicting (rare)
- **Memory**: ~50 bytes per entry (no expiry metadata)
- **No Overhead**: No time() calls, no expiry checks

### Comparison with ArrayCache

| Operation | InternalArrayCache | ArrayCache |
| --------- | ------------------ | ---------- |
| get() | O(1) | O(1) + expiry check |
| set() | O(1) | O(1) + calculateExpiry() |
| Memory/entry | ~50 bytes | ~100 bytes |
| TTL Support | ❌ | ✅ |

## [Use Cases](#use-cases)

- **CachedDetector**: Default cache (automatic)
- **Development**: Fast cache without external dependencies
- **Testing**: Predictable behavior without TTL
- **Single Process**: When persistence not needed
- **High Performance**: When TTL not required

## [Limitations](#limitations)

- **No TTL**: Entries never expire (until clearCache() or eviction)
- **No Persistence**: Data lost on script termination
- **Not Distributed**: Cannot share between processes
- **Simple LRU**: array_shift() is O(n) but rare
- **Memory Only**: Limited by PHP memory_limit

## [Best Practices](#best-practices)

1. **Use as Default**: Perfect for CachedDetector (automatic)
2. **Set Appropriate maxSize**: Balance memory vs hit rate
3. **Clear Between Batches**: Use clear() when processing distinct datasets
4. **Monitor Size**: Use getSize() to tune maxSize
5. **Use ArrayCache for TTL**: When expiration needed

## [Thread Safety](#thread-safety)

InternalArrayCache is **not thread-safe** across processes. Each process has its own isolated instance.

## [See Also](#see-also)

- [CachedDetector](CachedDetector.md) — Detector using InternalArrayCache by default
- [ArrayCache](ArrayCache.md) — Full-featured PSR-16 cache with TTL
- [PSR-16 Simple Cache](https://www.php-fig.org/psr/psr-16/) — PSR-16 specification
- [CharsetProcessor](CharsetProcessor.md) — Service using cached detection
