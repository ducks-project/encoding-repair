# PregMatchDetector

Fast encoding detector using `preg_match` for ASCII and UTF-8 detection.

## Overview

`PregMatchDetector` provides optimized detection for the most common encodings using regular expression patterns. It's approximately **70% faster** than `mb_detect_encoding` for ASCII/UTF-8 detection scenarios.

**Namespace:** `Ducks\Component\EncodingRepair\Detector`

**Implements:** [`DetectorInterface`](DetectorInterface.md)

## Supported Encodings

- **ASCII**: Pure 7-bit characters (0x00-0x7F)
- **UTF-8**: Valid multi-byte UTF-8 sequences

For other encodings, the detector returns `null` to allow the chain to try other detectors.

## Priority

**150** - Higher than `MbStringDetector` (100) but lower than `CachedDetector` (200).

## Methods

### detect()

```php
public function detect(string $string, ?array $options = null): ?string
```

Detects encoding using preg_match patterns.

**Parameters:**

- `$string` (string): String to analyze
- `$options` (?array): Detection options (unused)

**Returns:** `?string` - 'ASCII', 'UTF-8', or null if neither

**Algorithm:**

1. Empty strings return 'ASCII'
2. Strings with only bytes 0x00-0x7F return 'ASCII'
3. Strings passing UTF-8 validation (preg_match with 'u' modifier) return 'UTF-8'
4. All other strings return null

### getPriority()

```php
public function getPriority(): int
```

Returns detector priority (150).

### isAvailable()

```php
public function isAvailable(): bool
```

Checks if detector is available. Always returns `true` since `preg_match` is always available in PHP.

## Usage

### Basic Usage

```php
use Ducks\Component\EncodingRepair\Detector\PregMatchDetector;

$detector = new PregMatchDetector();

// ASCII detection
$encoding = $detector->detect('Hello World', []);
// Returns: 'ASCII'

// UTF-8 detection
$encoding = $detector->detect('Café', []);
// Returns: 'UTF-8'

// Other encodings
$iso = mb_convert_encoding('Café', 'ISO-8859-1', 'UTF-8');
$encoding = $detector->detect($iso, []);
// Returns: null (will try next detector in chain)
```

### With CharsetProcessor

```php
use Ducks\Component\EncodingRepair\CharsetProcessor;
use Ducks\Component\EncodingRepair\Detector\PregMatchDetector;

$processor = new CharsetProcessor();
$processor->registerDetector(new PregMatchDetector());

// Will use PregMatchDetector first (priority 150)
$encoding = $processor->detect('Hello World');
// Returns: 'ASCII'
```

### With CharsetHelper

```php
use Ducks\Component\EncodingRepair\CharsetHelper;
use Ducks\Component\EncodingRepair\Detector\PregMatchDetector;

CharsetHelper::registerDetector(new PregMatchDetector());

$encoding = CharsetHelper::detect('Café');
// Returns: 'UTF-8'
```

## Performance

PregMatchDetector is optimized for speed:

- **ASCII detection**: ~70% faster than mb_detect_encoding
- **UTF-8 validation**: Uses PCRE engine in C for efficient validation
- **Fast-path optimization**: Single regex check for ASCII-only strings

**Benchmark (10,000 detections):**

| String Type | PregMatchDetector | MbStringDetector | Improvement |
|-------------|-------------------|------------------|-------------|
| ASCII       | 12ms              | 40ms             | 70% faster  |
| UTF-8       | 18ms              | 45ms             | 60% faster  |
| ISO-8859-1  | 15ms              | 42ms             | N/A (null)  |

## Limitations

- Only detects ASCII and UTF-8
- Returns `null` for all other encodings (ISO-8859-1, Windows-1252, etc.)
- Should be used in a detector chain with fallback detectors

## Chain of Responsibility

PregMatchDetector is designed to work in a detector chain:

```text
CachedDetector (200)
    ↓ (cache miss)
PregMatchDetector (150) ← Fast ASCII/UTF-8 detection
    ↓ (returns null for other encodings)
MbStringDetector (100) ← Fallback for all encodings
    ↓ (returns null if cannot detect)
FileInfoDetector (50) ← Last resort
```

## See Also

- [DetectorInterface](DetectorInterface.md) - Detector contract
- [MbStringDetector](MbStringDetector.md) - Fallback detector for all encodings
- [CachedDetector](CachedDetector.md) - Caching decorator
- [DetectorChain](DetectorChain.md) - Chain coordinator
