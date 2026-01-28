# IconvCleaner

Cleaner using iconv() with //IGNORE to remove invalid sequences.

## Namespace

`Ducks\Component\EncodingRepair\Cleaner`

## Description

Uses `iconv()` with `//IGNORE` flag to skip invalid byte sequences.
Universal fallback that works with any encoding supported by iconv.

## Priority

10 (lowest - last resort)

## Methods

### clean()

```php
public function clean(string $data, string $encoding, array $options): ?string
```

Cleans invalid sequences using iconv //IGNORE.

**Parameters:**
- `$data` (string) - String to clean
- `$encoding` (string) - Target encoding
- `$options` (array) - Cleaning options (unused)

**Returns:** `?string` - Cleaned string or null on failure

### getPriority()

```php
public function getPriority(): int
```

**Returns:** `int` - 10

### isAvailable()

```php
public function isAvailable(): bool
```

**Returns:** `bool` - True if iconv() exists

## Performance

- **Corrupted data**: ~1.589μs (slowest)
- **Valid data**: ~1.634μs (slowest)
- **Stability**: ±2.09% deviation

## Example

```php
use Ducks\Component\EncodingRepair\Cleaner\IconvCleaner;

$cleaner = new IconvCleaner();
$cleaned = $cleaner->clean("Caf\xE9", 'UTF-8', []);
// Result: "Caf" (invalid byte ignored)
```

## See Also

- [CleanerInterface](CleanerInterface.md)
- [MbScrubCleaner](MbScrubCleaner.md)
- [PregMatchCleaner](PregMatchCleaner.md)
