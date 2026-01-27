# BomDetector

BOM (Byte Order Mark) detector for UTF encodings.

## Overview

BomDetector detects encoding by analyzing the Byte Order Mark at the beginning of the string. This is the **most reliable detection method** when BOM is present, providing 100% accuracy.

**Namespace:** `Ducks\Component\EncodingRepair\Detector`

**Implements:** [`DetectorInterface`](DetectorInterface.md)

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

### getPriority()

```php
public function getPriority(): int
```

Returns detector priority (160).

### isAvailable()

```php
public function isAvailable(): bool
```

Checks if detector is available. Always returns `true`.

## Usage

### Basic Usage

```php
use Ducks\Component\EncodingRepair\Detector\BomDetector;

$detector = new BomDetector();

// UTF-8 with BOM
$utf8Bom = "\xEF\xBB\xBF" . 'Hello World';
echo $detector->detect($utf8Bom, []); // 'UTF-8'

// UTF-16 LE with BOM
$utf16Le = "\xFF\xFE" . 'Hello';
echo $detector->detect($utf16Le, []); // 'UTF-16LE'

// No BOM
$noBom = 'Hello World';
echo $detector->detect($noBom, []); // null
```

### With CharsetHelper

```php
use Ducks\Component\EncodingRepair\CharsetHelper;
use Ducks\Component\EncodingRepair\Detector\BomDetector;

CharsetHelper::registerDetector(new BomDetector());

$content = file_get_contents('utf8-bom.txt');
$encoding = CharsetHelper::detect($content);
// Returns: 'UTF-8'
```

## Performance

- **O(1) complexity**: Only checks first 2-4 bytes
- **100% accuracy**: When BOM is present
- **Extremely fast**: Direct byte comparison

## Chain of Responsibility

```text
BomDetector (160) ← Highest priority
    ↓ (no BOM)
PregMatchDetector (150)
    ↓ (not ASCII/UTF-8)
MbStringDetector (100)
    ↓ (cannot detect)
FileInfoDetector (50)
```

## See Also

- [DetectorInterface](DetectorInterface.md)
- [PregMatchDetector](PregMatchDetector.md)
- [MbStringDetector](MbStringDetector.md)
