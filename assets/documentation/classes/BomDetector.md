# BomDetector

**Namespace:** `Ducks\Component\EncodingRepair\Detector`

**Implements:** [`DetectorInterface`](DetectorInterface.md)

BOM (Byte Order Mark) detector for UTF encodings.

## Overview

BomDetector detects encoding by analyzing the Byte Order Mark at the beginning of the string. This is the **most reliable detection method** when BOM is present, as it provides 100% accuracy.

## Supported BOMs

| Encoding | BOM Bytes | Hex |
|----------|-----------|-----|
| UTF-8 | EF BB BF | `\xEF\xBB\xBF` |
| UTF-16 LE | FF FE | `\xFF\xFE` |
| UTF-16 BE | FE FF | `\xFE\xFF` |
| UTF-32 LE | FF FE 00 00 | `\xFF\xFE\x00\x00` |
| UTF-32 BE | 00 00 FE FF | `\x00\x00\xFE\xFF` |

## Priority

**160** - Highest priority (BOM detection is the most reliable method).

## Methods

### detect()

```php
public function detect(string $string, ?array $options = null): ?string
```

Detects encoding by BOM signature.

**Parameters:**
- `$string` (string): String to analyze
- `$options` (?array): Detection options (unused)

**Returns:** `?string` - Detected encoding or null if no BOM found

**Algorithm:**
1. Check string length (minimum 2 bytes)
2. Check UTF-32 BOMs first (4 bytes) to avoid false positives with UTF-16
3. Check UTF-8 BOM (3 bytes)
4. Check UTF-16 BOMs (2 bytes)
5. Return null if no BOM found

### getPriority()

```php
public function getPriority(): int
```

Returns detector priority (160).

### isAvailable()

```php
public function isAvailable(): bool
```

Checks if detector is available. Always returns `true` (no dependencies).

## Usage

### Basic Usage

```php
use Ducks\Component\EncodingRepair\Detector\BomDetector;

$detector = new BomDetector();

// UTF-8 with BOM
$utf8Bom = "\xEF\xBB\xBF" . 'Hello World';
$encoding = $detector->detect($utf8Bom, []);
// Returns: 'UTF-8'

// UTF-16 LE with BOM
$utf16Le = "\xFF\xFE" . 'Hello';
$encoding = $detector->detect($utf16Le, []);
// Returns: 'UTF-16LE'

// No BOM
$noBom = 'Hello World';
$encoding = $detector->detect($noBom, []);
// Returns: null (will try next detector in chain)
```

### With CharsetProcessor

```php
use Ducks\Component\EncodingRepair\CharsetProcessor;
use Ducks\Component\EncodingRepair\Detector\BomDetector;

$processor = new CharsetProcessor();
$processor->registerDetector(new BomDetector());

// Will use BomDetector first (priority 160)
$encoding = $processor->detect("\xEF\xBB\xBF" . 'Hello');
// Returns: 'UTF-8'
```

### With CharsetHelper

```php
use Ducks\Component\EncodingRepair\CharsetHelper;
use Ducks\Component\EncodingRepair\Detector\BomDetector;

CharsetHelper::registerDetector(new BomDetector());

$encoding = CharsetHelper::detect("\xFF\xFE" . 'Hello');
// Returns: 'UTF-16LE'
```

## Use Cases

### Reading Files with BOM

```php
use Ducks\Component\EncodingRepair\CharsetHelper;
use Ducks\Component\EncodingRepair\Detector\BomDetector;

CharsetHelper::registerDetector(new BomDetector());

$content = file_get_contents('file.txt');
$encoding = CharsetHelper::detect($content);

if ('UTF-8' === $encoding) {
    // Remove BOM for processing
    $content = substr($content, 3);
}
```

### Converting Files with BOM

```php
$content = file_get_contents('utf16le.txt');

// Auto-detect with BOM
$utf8 = CharsetHelper::toCharset(
    $content,
    CharsetHelper::ENCODING_UTF8,
    CharsetHelper::AUTO
);

// BomDetector will detect UTF-16LE from BOM
```

## Performance

BomDetector is extremely fast:
- **O(1) complexity**: Only checks first 2-4 bytes
- **No statistical analysis**: Direct byte comparison
- **100% accuracy**: When BOM is present

## Limitations

- Only detects encodings with BOM
- Returns `null` for files without BOM (common for UTF-8)
- Cannot detect ISO-8859-1, Windows-1252, Shift_JIS, etc. (no BOM)

## Chain of Responsibility

BomDetector is designed to work at the top of the detector chain:

```text
BomDetector (160) ← Highest priority, 100% accurate when BOM present
    ↓ (returns null if no BOM)
PregMatchDetector (150) ← Fast ASCII/UTF-8 detection
    ↓ (returns null for other encodings)
MbStringDetector (100) ← Statistical detection for all encodings
    ↓ (returns null if cannot detect)
FileInfoDetector (50) ← Last resort
```

## See Also

- [DetectorInterface](DetectorInterface.md) - Detector contract
- [PregMatchDetector](PregMatchDetector.md) - Fast ASCII/UTF-8 detector
- [MbStringDetector](MbStringDetector.md) - Statistical detector
- [DetectorChain](DetectorChain.md) - Chain coordinator
