# PregMatchCleaner

Cleaner using preg_replace() to remove invalid UTF-8 sequences.

## Namespace

`Ducks\Component\EncodingRepair\Cleaner`

## Description

Uses `preg_replace()` to remove control characters and invalid UTF-8 sequences.
Fastest cleaner but only works with UTF-8 encoding.

## Priority

50 (medium)

## Methods

### clean()

```php
public function clean(string $data, string $encoding, array $options): ?string
```

Removes invalid UTF-8 control characters.

**Parameters:**
- `$data` (string) - String to clean
- `$encoding` (string) - Target encoding (must be UTF-8)
- `$options` (array) - Cleaning options (unused)

**Returns:** `?string` - Cleaned string or null if not UTF-8

### getPriority()

```php
public function getPriority(): int
```

**Returns:** `int` - 50

### isAvailable()

```php
public function isAvailable(): bool
```

**Returns:** `bool` - Always true

## Performance

- **Corrupted data**: ~0.896μs (fastest)
- **Valid data**: ~0.866μs (fastest)
- **Stability**: ±0.74% deviation (most stable)

## Pattern

Removes: `[\x00-\x08\x0B\x0C\x0E-\x1F\x7F]` (control characters)

## Example

```php
use Ducks\Component\EncodingRepair\Cleaner\PregMatchCleaner;

$cleaner = new PregMatchCleaner();
$cleaned = $cleaner->clean("Text\x00\x1F", 'UTF-8', []);
// Result: "Text"
```

## See Also

- [CleanerInterface](CleanerInterface.md)
- [MbScrubCleaner](MbScrubCleaner.md)
- [IconvCleaner](IconvCleaner.md)
