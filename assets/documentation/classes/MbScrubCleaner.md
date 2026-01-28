# MbScrubCleaner

Cleaner using mb_scrub() to remove invalid sequences.

## Namespace

`Ducks\Component\EncodingRepair\Cleaner`

## Description

Uses PHP's `mb_scrub()` function to remove invalid byte sequences.
Provides best cleaning quality but requires PHP 7.2+ with ext-mbstring.

## Priority

100 (highest - executed first)

## Methods

### clean()

```php
public function clean(string $data, string $encoding, array $options): ?string
```

Cleans invalid sequences using mb_scrub().

**Parameters:**
- `$data` (string) - String to clean
- `$encoding` (string) - Target encoding
- `$options` (array) - Cleaning options (unused)

**Returns:** `?string` - Cleaned string

### getPriority()

```php
public function getPriority(): int
```

**Returns:** `int` - 100

### isAvailable()

```php
public function isAvailable(): bool
```

**Returns:** `bool` - True if mb_scrub() exists

## Performance

- **Corrupted data**: ~1.02μs
- **Valid data**: ~1.05μs
- **Stability**: ±0.92% deviation

## Example

```php
use Ducks\Component\EncodingRepair\Cleaner\MbScrubCleaner;

$cleaner = new MbScrubCleaner();
$cleaned = $cleaner->clean("Caf\xC3\xA9 \xC2\x88", 'UTF-8', []);
// Result: "Café "
```

## See Also

- [CleanerInterface](CleanerInterface.md)
- [PregMatchCleaner](PregMatchCleaner.md)
- [IconvCleaner](IconvCleaner.md)
