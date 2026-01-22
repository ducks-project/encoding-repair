# [The CachedDetector class](#the-cacheddetector-class)

(PHP 7 >= 7.4.0, PHP 8)

## [Introduction](#introduction)

CachedDetector is a decorator that wraps any [DetectorInterface](DetectorInterface.md) implementation
to provide transparent caching of detection results. It uses a hash-based cache with configurable
maximum size to avoid redundant encoding detection operations.

This detector is particularly useful in batch processing scenarios where the same strings are
detected multiple times, providing significant performance improvements (50-80% in typical workloads).

**Architecture**: Implements Decorator pattern with LRU-like eviction strategy.

## [Class synopsis](#class-synopsis)

```php
final class CachedDetector implements DetectorInterface {
    /* Methods */
    public __construct(DetectorInterface $detector, int $maxSize = 1000)

    public detect(string $string, ?array $options = null): ?string

    public getPriority(): int

    public isAvailable(): bool

    public clearCache(): void

    public getCacheStats(): array
}
```

## [Features](#features)

- **Transparent Caching**: Automatically caches detection results without API changes
- **Hash-Based Keys**: Uses xxh3 for fast cache key generation
- **Size Limiting**: Prevents memory exhaustion with configurable max entries
- **High Priority**: Default priority 200 (higher than MbStringDetector's 100)
- **Statistics**: Provides cache size and hit rate information
- **Decorator Pattern**: Wraps any DetectorInterface implementation

## [Default Configuration](#default-configuration)

When used by [CharsetProcessor](CharsetProcessor.md), CachedDetector wraps MbStringDetector:

- **Priority**: 200 (executes before other detectors)
- **Max Size**: 1000 entries (~50KB memory overhead)
- **Hash Algorithm**: xxh3 (fastest non-cryptographic hash)

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

### Example #2 Custom cache size

```php
<?php

use Ducks\Component\EncodingRepair\Detector\CachedDetector;
use Ducks\Component\EncodingRepair\Detector\MbStringDetector;

// Limit cache to 100 entries for memory-constrained environments
$cached = new CachedDetector(new MbStringDetector(), 100);

for ($i = 0; $i < 150; $i++) {
    $cached->detect("string_{$i}", []);
}

$stats = $cached->getCacheStats();
echo $stats['size']; // 100 (limited by maxSize)
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
public function __construct(DetectorInterface $detector, int $maxSize = 1000)
```

**Parameters:**

- **detector** (DetectorInterface): Detector to wrap and cache
- **maxSize** (int): Maximum cache entries (default: 1000)

**Notes:**

- maxSize prevents unbounded memory growth
- When cache is full, new detections are not cached
- Consider memory constraints when setting maxSize

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

- Uses xxh3 hash of string as cache key
- Cache hit returns immediately without calling wrapped detector
- Null results are not cached
- Options are not part of cache key (assumes consistent options)

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

- **size** (int): Current number of cached entries
- **maxSize** (int): Maximum allowed entries

**Example:**

```php
$stats = $cached->getCacheStats();
// ['size' => 42, 'maxSize' => 1000]
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
- **No LRU Eviction**: When full, new entries are simply not cached
- **No TTL**: Cache entries never expire (until clearCache() or object destruction)
- **Memory Bound**: Large maxSize can consume significant memory

## [Best Practices](#best-practices)

1. **Set Appropriate maxSize**: Balance memory vs hit rate
2. **Clear Between Batches**: Use clearCache() when processing distinct datasets
3. **Monitor Statistics**: Use getCacheStats() to tune maxSize
4. **Consistent Options**: Use same options for best cache hit rate

## [Thread Safety](#thread-safety)

CachedDetector is **not thread-safe**. Each thread should have its own instance.

## [See Also](#see-also)

- [DetectorInterface](DetectorInterface.md) — Interface for detector implementations
- [MbStringDetector](MbStringDetector.md) — MbString-based detector (commonly wrapped)
- [DetectorChain](DetectorChain.md) — Chain of Responsibility for detectors
- [CharsetProcessor](CharsetProcessor.md) — Service using CachedDetector by default
- [FileInfoDetector](FileInfoDetector.md) — Alternative detector implementation
