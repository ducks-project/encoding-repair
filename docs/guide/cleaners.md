# String Cleaners

String cleaners remove invalid byte sequences from data before encoding conversion using the Chain of Responsibility pattern.

## Overview

Cleaners are executed in priority order and stop at the first successful result.
They are disabled by default but can be enabled with the `clean` option.

## Built-in Cleaners

### BomCleaner (Priority: 150)

Removes BOM (Byte Order Mark) from strings.

**Performance:**

- With BOM: ~0.7μs
- Without BOM: ~0.6μs (fast-path)
- Stability: ±0.5%

**Supported BOMs:** UTF-8, UTF-16 LE/BE, UTF-32 LE/BE

```php
use Ducks\Component\EncodingRepair\Cleaner\BomCleaner;

$cleaner = new BomCleaner();
$cleaned = $cleaner->clean("\xEF\xBB\xBFCafé", 'UTF-8', []);
// Result: "Café"
```

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

### NormalizerCleaner (Priority: 90)

Normalizes Unicode characters using NFC (Canonical Composition).

**Performance:**

- Decomposed: ~1.1μs
- Already normalized: ~1.0μs
- Stability: ±1.2%

**Requirements:** ext-intl

```php
use Ducks\Component\EncodingRepair\Cleaner\NormalizerCleaner;

$cleaner = new NormalizerCleaner();
// e + combining acute accent → é
$cleaned = $cleaner->clean("Cafe\u{0301}", 'UTF-8', []);
// Result: "Café"
```

### HtmlEntityCleaner (Priority: 60)

Decodes HTML entities to their character equivalents.

**Performance:**

- With entities: ~1.2μs
- Without entities: ~0.8μs (fast-path)
- Stability: ±1.5%

```php
use Ducks\Component\EncodingRepair\Cleaner\HtmlEntityCleaner;

$cleaner = new HtmlEntityCleaner();
$cleaned = $cleaner->clean('Caf&eacute; &amp; R&eacute;sum&eacute;', 'UTF-8', []);
// Result: "Café & Résumé"
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
| --------- | ---------------- | ------------ | ----------- |
| **BomCleaner** | 0.7μs | 0.6μs | ±0.5% |
| **PregMatchCleaner** | 0.896μs | 0.866μs | ±0.74% |
| MbScrubCleaner | 1.020μs | 1.051μs | ±0.92% |
| NormalizerCleaner | 1.1μs | 1.0μs | ±1.2% |
| HtmlEntityCleaner | 1.2μs | 0.8μs | ±1.5% |
| IconvCleaner | 1.589μs | 1.634μs | ±2.09% |
| CleanerChain | 1.709μs | 1.906μs | ±2.50% |

**Recommendation:** Default configuration (Bom → MbScrub → Normalizer → HtmlEntity → PregMatch → Iconv)
prioritizes quality and versatility.

## API Reference

- [CleanerInterface](../api/CleanerInterface.md)
- [CleanerChain](../api/CleanerChain.md)
- [BomCleaner](../api/BomCleaner.md)
- [MbScrubCleaner](../api/MbScrubCleaner.md)
- [NormalizerCleaner](../api/NormalizerCleaner.md)
- [HtmlEntityCleaner](../api/HtmlEntityCleaner.md)
- [PregMatchCleaner](../api/PregMatchCleaner.md)
- [IconvCleaner](../api/IconvCleaner.md)
