# IconvCleaner

Cleaner using iconv() with //IGNORE to remove invalid sequences.

## Namespace

```php
Ducks\Component\EncodingRepair\Cleaner
```

## Class

```php
final class IconvCleaner implements CleanerInterface
{
    public function clean(string $data, string $encoding, array $options): ?string;
    public function getPriority(): int;
    public function isAvailable(): bool;
}
```

## Description

Uses `iconv()` with `//IGNORE` flag to skip invalid byte sequences. Universal fallback for any encoding.

## Priority

10 (lowest - last resort)

## Requirements

- ext-iconv

## Methods

### clean()

```php
public function clean(string $data, string $encoding, array $options): ?string
```

**Returns:** Cleaned string or null on failure

### getPriority()

```php
public function getPriority(): int
```

**Returns:** 10

### isAvailable()

```php
public function isAvailable(): bool
```

**Returns:** True if iconv() exists

## Performance

- **Corrupted data**: ~1.589μs (slowest)
- **Valid data**: ~1.634μs (slowest)
- **Stability**: ±2.09%

## Usage

```php
use Ducks\Component\EncodingRepair\Cleaner\IconvCleaner;

$cleaner = new IconvCleaner();
$cleaned = $cleaner->clean("Caf\xE9", 'UTF-8', []);
// Result: "Caf" (invalid byte ignored)
```

## See Also

- [CleanerInterface](CleanerInterface.md)
- [String Cleaners Guide](../guide/cleaners.md)
