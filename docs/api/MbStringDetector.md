# MbStringDetector

Detector implementation using PHP's mbstring extension.

## Synopsis

```php
namespace Ducks\Component\EncodingRepair\Detector;

final class MbStringDetector implements DetectorInterface
{
    public function detect(string $string, array $options): ?string;
    public function getPriority(): int;
    public function isAvailable(): bool;
}
```

## Description

Uses `mb_detect_encoding()` to detect character encoding with strict mode enabled.

**Priority**: 100 (highest - most reliable)

**Requirements**: ext-mbstring (always available)

## Methods

### detect()

Detects encoding using mbstring extension.

```php
public function detect(string $string, array $options): ?string
```

**Parameters:**
- `$string` - String to analyze
- `$options` - Detection options
  - `encodings`: array - List of encodings to test (default: UTF-8, CP1252, ISO-8859-1, ASCII)

**Returns:** Detected encoding or null if detection fails

**Example:**

```php
use Ducks\Component\EncodingRepair\Detector\MbStringDetector;

$detector = new MbStringDetector();
$encoding = $detector->detect('Café', ['encodings' => ['UTF-8', 'ISO-8859-1']]);
echo $encoding; // "UTF-8"
```

### getPriority()

Returns detector priority.

```php
public function getPriority(): int
```

**Returns:** 100

### isAvailable()

Checks if mbstring extension is available.

```php
public function isAvailable(): bool
```

**Returns:** Always true (mbstring is required)

## See Also

- [DetectorInterface](DetectorInterface.md)
- [FileInfoDetector](FileInfoDetector.md)
- [DetectorChain](DetectorChain.md)
