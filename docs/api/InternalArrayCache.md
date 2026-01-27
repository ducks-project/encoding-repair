# InternalArrayCache

Optimized PSR-16 cache without TTL overhead.

## Overview

`InternalArrayCache` is a lightweight PSR-16 (Simple Cache) implementation designed specifically for [CachedDetector](CachedDetector.md). It provides pure O(1) operations without TTL overhead, making it the fastest possible cache implementation for detection results.

**New in v1.2**: Default cache for CachedDetector, optimized for performance.

## Class Synopsis

```php
namespace Ducks\Component\EncodingRepair\Cache;

final class InternalArrayCache implements CacheInterface
{
    public function __construct(int $maxSize = 1000);
    
    public function get($key, $default = null);
    public function set($key, $value, $ttl = null): bool;
    public function delete($key): bool;
    public function clear(): bool;
    public function getMultiple($keys, $default = null): iterable;
    public function setMultiple($values, $ttl = null): bool;
    public function deleteMultiple($keys): bool;
    public function has($key): bool;
    
    public function getSize(): int;
    public function getMaxSize(): int;
}
```

## Constructor

### __construct

```php
public function __construct(int $maxSize = 1000)
```

**Parameters:**

- `$maxSize` (int): Maximum cache entries (default: 1000)

## Key Features

- **Zero TTL Overhead**: No expiration calculation or checks
- **Pure O(1) Operations**: Direct array access without wrapping
- **LRU Eviction**: Simple array_shift() when max size reached
- **Minimal Memory**: ~50 bytes per entry (no expiry metadata)
- **Default for CachedDetector**: Automatic instantiation

## Examples

### Default Usage (Automatic)

```php
use Ducks\Component\EncodingRepair\Detector\CachedDetector;
use Ducks\Component\EncodingRepair\Detector\MbStringDetector;

// InternalArrayCache is used automatically
$detector = new CachedDetector(new MbStringDetector());

$encoding = $detector->detect('Café', []);
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

## Performance

| Operation | Complexity | Notes |
| --------- | ---------- | ----- |
| get() | O(1) | Direct array access |
| set() | O(1) avg | O(n) when evicting (rare) |
| Memory/entry | ~50 bytes | No expiry metadata |

## Use Cases

- **CachedDetector**: Default cache (automatic)
- **Development**: Fast cache without external dependencies
- **Testing**: Predictable behavior without TTL
- **High Performance**: When TTL not required

## Limitations

- **No TTL**: Entries never expire (until clearCache() or eviction)
- **No Persistence**: Data lost on script termination
- **Not Distributed**: Cannot share between processes
- **Memory Only**: Limited by PHP memory_limit

## See Also

- [CachedDetector](CachedDetector.md)
- [ArrayCache](ArrayCache.md)
- [PSR-16 Simple Cache](https://www.php-fig.org/psr/psr-16/)
