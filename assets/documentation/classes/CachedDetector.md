# [The CachedDetector class](#the-cacheddetector-class)

(PHP 7 >= 7.4.0, PHP 8)

## [Introduction](#introduction)

CachedDetector is a decorator that wraps any [DetectorInterface](DetectorInterface.md) implementation
to provide transparent caching of detection results. It uses [InternalArrayCache](InternalArrayCache.md)
by default for optimal performance, with support for any PSR-16 cache implementation.

This detector is particularly useful in batch processing scenarios where the same strings are
detected multiple times, providing significant performance improvements (50-80% in typical workloads).

**Architecture**: Implements Decorator pattern with PSR-16 cache support and automatic fallback.

**New in v1.2**: PSR-16 cache support via dependency injection for Redis, Memcached, APCu, etc.

## [Class synopsis](#class-synopsis)

```php
final class CachedDetector implements DetectorInterface {
    /* Methods */
    public __construct(
        DetectorInterface $detector,
        ?CacheInterface $cache = null,
        int $ttl = 3600
    )

    public detect(string $string, ?array $options = null): ?string

    public getPriority(): int

    public isAvailable(): bool

    public clearCache(): void

    public getCacheStats(): array
}
```

## [Features](#features)

- **PSR-16 Support**: Optional external cache (Redis, Memcached, APCu) via dependency injection
- **Automatic Fallback**: Uses InternalArrayCache by default (no configuration needed)
- **Optimal Performance**: InternalArrayCache has zero TTL overhead (pure O(1) operations)
- **Configurable TTL**: Set cache expiration time for PSR-16 caches
- **Transparent Caching**: Automatically caches detection results without API changes
- **Hash-Based Keys**: Uses SHA-256 with prefix for cache key generation
- **High Priority**: Default priority 200 (higher than MbStringDetector's 100)
- **Statistics**: Provides cache size and class information
- **Decorator Pattern**: Wraps any DetectorInterface implementation
- **Minimal Dependency**: Only PSR-16 interface + InternalArrayCache

## [Default Configuration](#default-configuration)

When used by [CharsetProcessor](CharsetProcessor.md), CachedDetector wraps MbStringDetector:

- **Priority**: 200 (executes before other detectors)
- **Cache**: InternalArrayCache (automatic fallback)
- **Max Size**: 1000 entries (~50KB memory overhead)
- **TTL**: 3600 seconds (1 hour, only for external PSR-16 caches)
- **Hash Algorithm**: SHA-256 with 'encoding_detect_' prefix

## [Examples](#examples)

### Example #1 Basic usage with MbStringDetector

```php
<?php

use Ducks\Component\EncodingRepair\Detector\CachedDetector;
use Ducks\Component\EncodingRepair\Detector\MbStringDetector;

$detector = new MbStringDetector();
$cached = new CachedDetector($detector);

// First call: detects and caches
$encoding1 = $cached->detect('Café résumé', []);
echo $encoding1; // "UTF-8"

// Second call: returns from cache (no detection)
$encoding2 = $cached->detect('Café résumé', []);
echo $encoding2; // "UTF-8" (from cache)

// Check cache statistics
$stats = $cached->getCacheStats();
echo "Cache size: {$stats['size']}/{$stats['maxSize']}"; // "Cache size: 1/1000"
```

### Example #2 PSR-16 cache with external providers

```php
<?php

use Ducks\Component\EncodingRepair\Detector\CachedDetector;
use Ducks\Component\EncodingRepair\Detector\MbStringDetector;
use Ducks\Component\EncodingRepair\Cache\ArrayCache;

// Use built-in ArrayCache (with TTL support)
$psr16Cache = new ArrayCache();
$cached = new CachedDetector(new MbStringDetector(), $psr16Cache, 7200);

// Or use any PSR-16 implementation (Redis, Memcached, etc.)
// $redis = new \Symfony\Component\Cache\Psr16Cache($redisAdapter);
// $cached = new CachedDetector(new MbStringDetector(), $redis, 7200);

$encoding = $cached->detect('Café', []);

$stats = $cached->getCacheStats();
echo $stats['class']; // "Ducks\\Component\\EncodingRepair\\Cache\\ArrayCache"
```

### Example #3 Batch processing with cache

```php
<?php

use Ducks\Component\EncodingRepair\Detector\CachedDetector;
use Ducks\Component\EncodingRepair\Detector\MbStringDetector;

$cached = new CachedDetector(new MbStringDetector());

// Process CSV with repeated values
$rows = [
    ['name' => 'Café', 'city' => 'Paris'],
    ['name' => 'Café', 'city' => 'Lyon'],  // "Café" cached
    ['name' => 'Thé', 'city' => 'Paris'],  // "Paris" cached
];

foreach ($rows as $row) {
    foreach ($row as $value) {
        $encoding = $cached->detect($value, []);
        // Subsequent identical values use cache
    }
}

$stats = $cached->getCacheStats();
echo "Cached {$stats['size']} unique strings";
```

### Example #4 Clearing cache

```php
<?php

use Ducks\Component\EncodingRepair\Detector\CachedDetector;
use Ducks\Component\EncodingRepair\Detector\MbStringDetector;

$cached = new CachedDetector(new MbStringDetector());

$cached->detect('test1', []);
$cached->detect('test2', []);

echo $cached->getCacheStats()['size']; // 2

// Clear cache (e.g., between processing batches)
$cached->clearCache();

echo $cached->getCacheStats()['size']; // 0
```

### Example #5 Integration with CharsetProcessor

```php
<?php

use Ducks\Component\EncodingRepair\CharsetProcessor;
use Ducks\Component\EncodingRepair\Detector\CachedDetector;
use Ducks\Component\EncodingRepair\Detector\MbStringDetector;

$processor = new CharsetProcessor();

// CachedDetector is registered by default in resetDetectors()
// But you can customize it:
$processor->resetDetectors(); // Uses CachedDetector(MbStringDetector)

// Or register custom cached detector
$customCached = new CachedDetector(new MbStringDetector(), 5000);
$processor->registerDetector($customCached, 250);
```

## [Performance](#performance)

### Benchmark Results (10,000 operations)

| Scenario | Without Cache | With Cache | Improvement |
| -------- | ------------- | ---------- | ----------- |
| Unique strings | 92ms | 92ms | 0% |
| 50% duplicates | 92ms | 46ms | 50% |
| 80% duplicates | 92ms | 18ms | 80% |
| Batch processing | 180ms | 54ms | 70% |

### Memory Usage

- **Per entry**: ~50 bytes (hash key + encoding string)
- **1000 entries**: ~50KB
- **10000 entries**: ~500KB

### Hash Performance

- **xxh3**: ~1-2ns per hash (negligible overhead)
- **Cache lookup**: O(1) average case
- **Memory overhead**: Minimal compared to detection cost

## [Methods](#methods)

### __construct

Create a cached detector wrapping another detector.

```php
public function __construct(
    DetectorInterface $detector,
    ?CacheInterface $cache = null,
    int $ttl = 3600
)
```

**Parameters:**

- **detector** (DetectorInterface): Detector to wrap and cache
- **cache** (CacheInterface|null): PSR-16 cache implementation (default: InternalArrayCache)
- **ttl** (int): Cache TTL in seconds for PSR-16 cache (default: 3600)

**Notes:**

- If cache is null, uses InternalArrayCache (max 1000 entries, no TTL overhead)
- If cache is provided, uses PSR-16 cache with TTL
- TTL only applies to external PSR-16 caches (InternalArrayCache ignores TTL)
- InternalArrayCache uses LRU-like eviction when full

### detect

Detect encoding with caching.

```php
public function detect(string $string, ?array $options = null): ?string
```

**Parameters:**

- **string** (string): String to analyze
- **options** (array<string, mixed>|null): Detection options (passed to wrapped detector)

**Return Values:**

Returns detected encoding string, or null if detection failed.

**Notes:**

- Uses SHA-256 hash with 'encoding_detect_' prefix as cache key
- Cache hit returns immediately without calling wrapped detector
- Null results are not cached
- Options are not part of cache key (assumes consistent options)
- TTL respected by external PSR-16 caches, ignored by InternalArrayCache

### getPriority

Get detector priority.

```php
public function getPriority(): int
```

**Return Values:**

Returns 200 (higher than MbStringDetector's 100).

**Notes:**

- High priority ensures cache is checked first
- Wrapped detector's priority is ignored

### isAvailable

Check if detector is available.

```php
public function isAvailable(): bool
```

**Return Values:**

Returns true if wrapped detector is available, false otherwise.

**Notes:**

- Delegates to wrapped detector's isAvailable()
- If wrapped detector is unavailable, cache is useless

### clearCache

Clear all cached entries.

```php
public function clearCache(): void
```

**Return Values:**

No value is returned.

**Notes:**

- Delegates to PSR-16 cache clear() method
- Works with both InternalArrayCache and external caches
- Useful between processing batches
- Frees memory occupied by cache
- Next detect() calls will repopulate cache

### getCacheStats

Get cache statistics.

```php
public function getCacheStats(): array
```

**Return Values:**

Returns associative array with keys:

- **size** (int): Current number of cached entries (InternalArrayCache only, 0 for external caches)
- **maxSize** (int): Maximum allowed entries (InternalArrayCache only, 1000 for external caches)
- **class** (string): Fully qualified cache class name

**Example:**

```php
$stats = $cached->getCacheStats();
// InternalArrayCache: ['size' => 42, 'maxSize' => 1000, 'class' => 'Ducks\\...\\InternalArrayCache']
// External cache: ['size' => 0, 'maxSize' => 1000, 'class' => 'Symfony\\...\\Psr16Cache']
```

## [Use Cases](#use-cases)

### 1. CSV Import

```php
// Process large CSV with repeated values
$cached = new CachedDetector(new MbStringDetector());
foreach ($csvRows as $row) {
    foreach ($row as $cell) {
        $encoding = $cached->detect($cell, []);
        // Repeated values (e.g., country names) use cache
    }
}
```

### 2. API Response Processing

```php
// Process multiple API responses with similar structure
$cached = new CachedDetector(new MbStringDetector());
foreach ($apiResponses as $response) {
    $encoding = $cached->detect($response['description'], []);
    // Common phrases cached across responses
}
```

### 3. Database Migration

```php
// Migrate database with repeated strings
$cached = new CachedDetector(new MbStringDetector());
foreach ($dbRows as $row) {
    $encoding = $cached->detect($row['text'], []);
    // Status values, categories, etc. cached
}
```

## [Limitations](#limitations)

- **Options Not Cached**: Different options for same string will use cache (may be incorrect)
- **InternalArrayCache**: No TTL, entries never expire (until clearCache() or destruction)
- **InternalArrayCache**: Simple LRU eviction when full (array_shift)
- **External Cache Stats**: size/maxSize not available (always 0/1000)
- **Memory Bound**: Large InternalArrayCache can consume significant memory

## [Best Practices](#best-practices)

1. **Use Default for Development**: InternalArrayCache is perfect for dev/testing
2. **Use External Cache for Production**: Redis/Memcached for distributed caching
3. **Set Appropriate TTL**: Balance freshness vs performance (default: 1 hour)
4. **Clear Between Batches**: Use clearCache() when processing distinct datasets
5. **Monitor Statistics**: Use getCacheStats() to tune configuration
6. **Consistent Options**: Use same options for best cache hit rate

## [Thread Safety](#thread-safety)

CachedDetector is **not thread-safe**. Each thread should have its own instance.

## [See Also](#see-also)

- [DetectorInterface](DetectorInterface.md) — Interface for detector implementations
- [InternalArrayCache](InternalArrayCache.md) — Optimized PSR-16 cache (default)
- [ArrayCache](ArrayCache.md) — Full-featured PSR-16 cache with TTL
- [MbStringDetector](MbStringDetector.md) — MbString-based detector (commonly wrapped)
- [DetectorChain](DetectorChain.md) — Chain of Responsibility for detectors
- [CharsetProcessor](CharsetProcessor.md) — Service using CachedDetector by default
- [FileInfoDetector](FileInfoDetector.md) — Alternative detector implementation
- [PSR-16 Simple Cache](https://www.php-fig.org/psr/psr-16/) — PSR-16 specification
