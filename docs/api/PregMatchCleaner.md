# PregMatchCleaner

Cleaner using preg_replace() to remove invalid UTF-8 sequences.

## Namespace

```php
Ducks\Component\EncodingRepair\Cleaner
```

## Class

```php
final class PregMatchCleaner implements CleanerInterface
{
    public function clean(string $data, string $encoding, array $options): ?string;
    public function getPriority(): int;
    public function isAvailable(): bool;
}
```

## Description

Uses `preg_replace()` to remove control characters. Fastest cleaner but only works with UTF-8.

## Priority

50 (medium)

## Pattern

Removes: `[\x00-\x08\x0B\x0C\x0E-\x1F\x7F]` (control characters)

## Methods

### clean()

```php
public function clean(string $data, string $encoding, array $options): ?string
```

**Returns:** Cleaned string or null if not UTF-8

### getPriority()

```php
public function getPriority(): int
```

**Returns:** 50

### isAvailable()

```php
public function isAvailable(): bool
```

**Returns:** Always true

## Performance

- **Corrupted data**: ~0.896μs (fastest)
- **Valid data**: ~0.866μs (fastest)
- **Stability**: ±0.74% (most stable)

## Usage

```php
use Ducks\Component\EncodingRepair\Cleaner\PregMatchCleaner;

$cleaner = new PregMatchCleaner();
$cleaned = $cleaner->clean("Text\x00\x1F", 'UTF-8', []);
// Result: "Text"
```

## See Also

- [CleanerInterface](CleanerInterface.md)
- [String Cleaners Guide](../guide/cleaners.md)
