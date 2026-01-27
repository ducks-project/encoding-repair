# CachedDetector

Cached detector decorator with PSR-16 support.

## Overview

`CachedDetector` is a decorator that wraps any `DetectorInterface` implementation to provide transparent caching of detection results. It uses [InternalArrayCache](InternalArrayCache.md) by default for optimal performance, with support for any PSR-16 cache implementation.

**New in v1.2**: PSR-16 cache support via dependency injection for Redis, Memcached, APCu, etc.

## Class Synopsis

```php
namespace Ducks\Component\EncodingRepair\Detector;

final class CachedDetector implements DetectorInterface
{
    public function __construct(
        DetectorInterface $detector,
        ?CacheInterface $cache = null,
        int $ttl = 3600
    );

    public function detect(string $string, ?array $options = null): ?string;
    public function getPriority(): int;
    public function isAvailable(): bool;
    public function clearCache(): void;
    public function getCacheStats(): array;
}
```

## Constructor

### __construct

```php
public function __construct(
    DetectorInterface $detector,
    ?CacheInterface $cache = null,
    int $ttl = 3600
)
```

**Parameters:**

- `$detector` (DetectorInterface): Detector to wrap and cache
- `$cache` (CacheInterface|null): PSR-16 cache implementation (default: InternalArrayCache)
- `$ttl` (int): Cache TTL in seconds for PSR-16 cache (default: 3600)

**Notes:**

- If `$cache` is null, uses InternalArrayCache (max 1000 entries, no TTL overhead)
- If `$cache` is provided, uses PSR-16 cache with TTL
- TTL only applies to external PSR-16 caches (InternalArrayCache ignores TTL)

## Methods

### detect

Detect encoding with caching.

```php
public function detect(string $string, ?array $options = null): ?string
```

**Parameters:**

- `$string` (string): String to analyze
- `$options` (array|null): Detection options (passed to wrapped detector)

**Returns:** Detected encoding string, or null if detection failed.

### getPriority

Get detector priority.

```php
public function getPriority(): int
```

**Returns:** 200 (higher than MbStringDetector's 100)

### isAvailable

Check if detector is available.

```php
public function isAvailable(): bool
```

**Returns:** true if wrapped detector is available, false otherwise.

### clearCache

Clear all cached entries.

```php
public function clearCache(): void
```

Clears both PSR-16 cache (if provided) and internal cache.

### getCacheStats

Get cache statistics.

```php
public function getCacheStats(): array
```

**Returns:** Array with keys:

- `size` (int): Current number of cached entries (internal cache only)
- `maxSize` (int): Maximum allowed entries (internal cache only)
- `type` (string): Cache type ('psr16' or 'internal')

## Examples

### Basic Usage

```php
use Ducks\Component\EncodingRepair\Detector\CachedDetector;
use Ducks\Component\EncodingRepair\Detector\MbStringDetector;

$detector = new MbStringDetector();
$cached = new CachedDetector($detector);

// First call: detects and caches
$encoding1 = $cached->detect('Café résumé', []);

// Second call: returns from cache
$encoding2 = $cached->detect('Café résumé', []);
```

### PSR-16 Cache

```php
use Ducks\Component\EncodingRepair\Cache\ArrayCache;
use Ducks\Component\EncodingRepair\Detector\CachedDetector;
use Ducks\Component\EncodingRepair\Detector\MbStringDetector;

// Use ArrayCache when TTL is needed
$psr16Cache = new ArrayCache();
$cached = new CachedDetector(new MbStringDetector(), $psr16Cache, 7200);

// Or use any PSR-16 implementation
// $redis = new \Symfony\Component\Cache\Psr16Cache($redisAdapter);
// $cached = new CachedDetector(new MbStringDetector(), $redis, 7200);
```

## See Also

- [DetectorInterface](DetectorInterface.md)
- [InternalArrayCache](InternalArrayCache.md)
- [ArrayCache](ArrayCache.md)
- [MbStringDetector](MbStringDetector.md)
- [CharsetProcessor](CharsetProcessor.md)
