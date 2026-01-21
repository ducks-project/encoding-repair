# Advanced Usage

## Repair Double-Encoded Data

### Fix Corrupted Strings

```php
use Ducks\Component\EncodingRepair\CharsetHelper;

// Common issue: UTF-8 data stored in Latin1 column, then read as UTF-8
$corrupted = "CafÃ©"; // Should be "Café"

$fixed = CharsetHelper::repair($corrupted);
echo $fixed; // "Café"

// With custom max depth
$fixed = CharsetHelper::repair(
    $corrupted,
    CharsetHelper::ENCODING_UTF8,
    CharsetHelper::ENCODING_ISO,
    ['maxDepth' => 10]  // Try to peel up to 10 encoding layers
);
```

## Custom Transcoder

### Add Support for Custom Encoding

```php
use Ducks\Component\EncodingRepair\CharsetHelper;

// Register custom transcoder
CharsetHelper::registerTranscoder(
    function (string $data, string $to, string $from, array $options): ?string {
        if ($from === 'MY-CUSTOM-ENCODING') {
            return myCustomConversion($data, $to);
        }
        // Return null to try next transcoder in chain
        return null;
    },
    true  // Prepend (higher priority)
);

// Now use it
$result = CharsetHelper::toCharset($data, 'UTF-8', 'MY-CUSTOM-ENCODING');
```

## Custom Detector

### Detect Proprietary Format

```php
use Ducks\Component\EncodingRepair\CharsetHelper;

CharsetHelper::registerDetector(
    function (string $string, array $options): ?string {
        // Check for UTF-16 BOM
        if (strlen($string) >= 2) {
            if (ord($string[0]) === 0xFF && ord($string[1]) === 0xFE) {
                return 'UTF-16LE';
            }
            if (ord($string[0]) === 0xFE && ord($string[1]) === 0xFF) {
                return 'UTF-16BE';
            }
        }
        return null;
    },
    true  // Prepend (higher priority)
);
```

## Chain of Responsibility Pattern

CharsetHelper uses multiple strategies with automatic fallback:

```text
UConverter (intl) → iconv → mbstring
     ↓ (fails)         ↓ (fails)    ↓ (always works)
```

**Transcoder priorities:**

1. **UConverter** (requires `ext-intl`): Best precision, supports many encodings
2. **iconv**: Good performance, supports transliteration
3. **mbstring**: Universal fallback, most permissive

**Detector priorities:**

1. **mb_detect_encoding**: Fast and reliable for common encodings
2. **finfo (FileInfo)**: Fallback for difficult cases

## Performance Optimization

### Cache Detection Results

```php
$cache = [];

function convertWithCache(string $data): string
{
    static $cache = [];
    $hash = md5($data);
    
    if (!isset($cache[$hash])) {
        $cache[$hash] = CharsetHelper::toUtf8($data);
    }
    
    return $cache[$hash];
}
```

### Use Specific Encodings

```php
// Good: Specific encoding
$result = CharsetHelper::toUtf8($data, CharsetHelper::WINDOWS_1252);

// Avoid: Auto-detection when encoding is known
$result = CharsetHelper::toUtf8($data, CharsetHelper::AUTO);
```

## Error Handling

### Handle Invalid Encodings

```php
try {
    $result = CharsetHelper::toCharset($data, 'UTF-8', 'INVALID');
} catch (InvalidArgumentException $e) {
    error_log($e->getMessage());
}
```

### Handle JSON Errors

```php
try {
    $json = CharsetHelper::safeJsonEncode($data);
} catch (RuntimeException $e) {
    error_log("JSON encoding failed: " . $e->getMessage());
    return json_encode(['error' => 'Encoding failed']);
}
```

## Next Steps

- [Use Cases](use-cases.md)
- [API Reference](../api/CharsetHelper.md)
- [Contributing](../contributing/development.md)
