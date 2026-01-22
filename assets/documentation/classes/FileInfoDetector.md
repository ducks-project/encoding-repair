# FileInfoDetector

Detector implementation using PHP's FileInfo extension.

## Synopsis

```php
namespace Ducks\Component\EncodingRepair\Detector;

final class FileInfoDetector implements DetectorInterface
{
    public function detect(string $string, array $options): ?string;
    public function getPriority(): int;
    public function isAvailable(): bool;
}
```

## Description

Uses `finfo` class with `FILEINFO_MIME_ENCODING` to detect character encoding.

**Priority**: 50 (fallback detector)

**Requirements**: ext-fileinfo (optional)

## Methods

### detect()

Detects encoding using FileInfo extension.

```php
public function detect(string $string, array $options): ?string
```

**Parameters:**
- `$string` - String to analyze
- `$options` - Detection options
  - `finfo_magic`: string - Custom magic database file path
  - `finfo_flags`: int - FileInfo flags (default: FILEINFO_NONE)
  - `finfo_context`: resource - Stream context

**Returns:** Detected encoding (uppercase) or null if detection fails or returns 'binary'

**Example:**

```php
use Ducks\Component\EncodingRepair\Detector\FileInfoDetector;

$detector = new FileInfoDetector();
$encoding = $detector->detect('Café', []);
echo $encoding; // "UTF-8"
```

### getPriority()

Returns detector priority.

```php
public function getPriority(): int
```

**Returns:** 50

### isAvailable()

Checks if FileInfo extension is available.

```php
public function isAvailable(): bool
```

**Returns:** true if finfo class exists, false otherwise

## See Also

- [DetectorInterface](DetectorInterface.md)
- [MbStringDetector](MbStringDetector.md)
- [DetectorChain](DetectorChain.md)
