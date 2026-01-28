# String Cleaners

String cleaners remove invalid byte sequences from data before encoding conversion using the Chain of Responsibility pattern.

## Overview

Cleaners are executed in priority order and stop at the first successful result. They are disabled by default but can be enabled with the `clean` option.

## Built-in Cleaners

### MbScrubCleaner (Priority: 100)

Uses PHP's `mb_scrub()` function for best cleaning quality.

**Performance:**
- Corrupted data: ~1.02μs
- Valid data: ~1.05μs
- Stability: ±0.92%

**Requirements:** PHP 7.2+ with ext-mbstring

```php
use Ducks\Component\EncodingRepair\Cleaner\MbScrubCleaner;

$cleaner = new MbScrubCleaner();
$cleaned = $cleaner->clean("Caf\xC3\xA9 \xC2\x88", 'UTF-8', []);
```

### PregMatchCleaner (Priority: 50)

Uses `preg_replace()` to remove control characters. Fastest cleaner but UTF-8 only.

**Performance:**
- Corrupted data: ~0.896μs (fastest)
- Valid data: ~0.866μs (fastest)
- Stability: ±0.74% (most stable)

**Pattern:** Removes `[\x00-\x08\x0B\x0C\x0E-\x1F\x7F]`

```php
use Ducks\Component\EncodingRepair\Cleaner\PregMatchCleaner;

$cleaner = new PregMatchCleaner();
$cleaned = $cleaner->clean("Text\x00\x1F", 'UTF-8', []);
```

### IconvCleaner (Priority: 10)

Uses `iconv()` with `//IGNORE` flag. Universal fallback for any encoding.

**Performance:**
- Corrupted data: ~1.589μs (slowest)
- Valid data: ~1.634μs (slowest)
- Stability: ±2.09%

**Requirements:** ext-iconv

```php
use Ducks\Component\EncodingRepair\Cleaner\IconvCleaner;

$cleaner = new IconvCleaner();
$cleaned = $cleaner->clean("Caf\xE9", 'UTF-8', []);
```

## Usage

### Enabling Cleaners

Cleaners are disabled by default (`clean: false`) but enabled in `repair()`:

```php
use Ducks\Component\EncodingRepair\CharsetHelper;

// Disabled by default
$result = CharsetHelper::toUtf8($data, 'ISO-8859-1');

// Enable with option
$result = CharsetHelper::toUtf8($data, 'ISO-8859-1', ['clean' => true]);

// Automatically enabled in repair()
$fixed = CharsetHelper::repair($corruptedData);
```

### Using CharsetProcessor

```php
use Ducks\Component\EncodingRepair\CharsetProcessor;

$processor = new CharsetProcessor();

// Use with clean option
$result = $processor->toUtf8($data, 'ISO-8859-1', ['clean' => true]);
```

## Custom Cleaners

Implement `CleanerInterface` to create custom cleaners:

```php
use Ducks\Component\EncodingRepair\Cleaner\CleanerInterface;

class CustomCleaner implements CleanerInterface
{
    public function clean(string $data, string $encoding, array $options): ?string
    {
        // Remove non-printable ASCII
        return preg_replace('/[^\x20-\x7E]/', '', $data);
    }

    public function getPriority(): int
    {
        return 75; // Between PregMatch (50) and MbScrub (100)
    }

    public function isAvailable(): bool
    {
        return true;
    }
}
```

### Registering Custom Cleaners

```php
use Ducks\Component\EncodingRepair\CharsetProcessor;

$processor = new CharsetProcessor();
$processor->registerCleaner(new CustomCleaner());

// Use with clean option
$result = $processor->toUtf8($data, 'ISO-8859-1', ['clean' => true]);
```

### Managing Cleaners

```php
// Reset to defaults
$processor->resetCleaners();

// Unregister specific cleaner
$cleaner = new CustomCleaner();
$processor->registerCleaner($cleaner);
$processor->unregisterCleaner($cleaner);
```

## CleanerChain

The `CleanerChain` coordinates multiple cleaners with priority-based execution:

```php
use Ducks\Component\EncodingRepair\Cleaner\CleanerChain;
use Ducks\Component\EncodingRepair\Cleaner\MbScrubCleaner;
use Ducks\Component\EncodingRepair\Cleaner\PregMatchCleaner;

$chain = new CleanerChain();
$chain->register(new MbScrubCleaner());
$chain->register(new PregMatchCleaner());

$cleaned = $chain->clean($corruptedString, 'UTF-8', []);
```

### Execution Flow

1. Check `isAvailable()` for each cleaner
2. Execute `clean()` in priority order (highest first)
3. Stop at first non-null result
4. Return null if all cleaners fail

## Performance Comparison

| Cleaner | Corrupted Data | Valid Data | Stability |
|---------|----------------|------------|-----------|
| **PregMatchCleaner** | 0.896μs | 0.866μs | ±0.74% |
| MbScrubCleaner | 1.020μs | 1.051μs | ±0.92% |
| IconvCleaner | 1.589μs | 1.634μs | ±2.09% |
| CleanerChain | 1.709μs | 1.906μs | ±2.50% |

**Recommendation:** Default configuration (MbScrub → PregMatch → Iconv) prioritizes quality over speed.

## API Reference

- [CleanerInterface](../api/CleanerInterface.md)
- [CleanerChain](../api/CleanerChain.md)
- [MbScrubCleaner](../api/MbScrubCleaner.md)
- [PregMatchCleaner](../api/PregMatchCleaner.md)
- [IconvCleaner](../api/IconvCleaner.md)
