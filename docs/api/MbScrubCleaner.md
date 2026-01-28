# MbScrubCleaner

Cleaner using mb_scrub() to remove invalid sequences.

## Namespace

```php
Ducks\Component\EncodingRepair\Cleaner
```

## Class

```php
final class MbScrubCleaner implements CleanerInterface
{
    public function clean(string $data, string $encoding, array $options): ?string;
    public function getPriority(): int;
    public function isAvailable(): bool;
}
```

## Description

Uses PHP's `mb_scrub()` function to remove invalid byte sequences. Provides best cleaning quality.

## Priority

100 (highest - executed first)

## Requirements

- PHP 7.2+
- ext-mbstring

## Methods

### clean()

```php
public function clean(string $data, string $encoding, array $options): ?string
```

**Returns:** Cleaned string using mb_scrub()

### getPriority()

```php
public function getPriority(): int
```

**Returns:** 100

### isAvailable()

```php
public function isAvailable(): bool
```

**Returns:** True if mb_scrub() exists

## Performance

- **Corrupted data**: ~1.02μs
- **Valid data**: ~1.05μs
- **Stability**: ±0.92%

## Usage

```php
use Ducks\Component\EncodingRepair\Cleaner\MbScrubCleaner;

$cleaner = new MbScrubCleaner();
$cleaned = $cleaner->clean("Caf\xC3\xA9 \xC2\x88", 'UTF-8', []);
// Result: "Café "
```

## See Also

- [CleanerInterface](CleanerInterface.md)
- [String Cleaners Guide](../guide/cleaners.md)
