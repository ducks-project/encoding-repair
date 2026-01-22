# CharsetHelper::detectBatch

(PHP 7 >= 7.4.0, PHP 8)

CharsetHelper::detectBatch — Batch detects the charset encoding of iterable items

## Description

```php
public static CharsetHelper::detectBatch(
    iterable $items,
    array $options = []
): string
```

Detects the character encoding from the first non-empty string in an iterable collection. This method is useful for determining the encoding of homogeneous data sets before batch processing.

## Parameters

**items**

Iterable collection of items to analyze. The method will iterate until it finds the first non-empty string value.

**options**

Optional array of detection options:

- `encodings` (array): List of encodings to test (default: ['UTF-8', 'CP1252', 'ISO-8859-1', 'ASCII'])

## Return Values

Returns the detected encoding as an uppercase string (e.g., 'UTF-8', 'ISO-8859-1'). If no string is found or detection fails, returns 'ISO-8859-1' as fallback.

## Examples

### Example #1 Detect encoding from array

```php
<?php
use Ducks\Component\EncodingRepair\CharsetHelper;

$data = ['Café', 'résumé', 'naïve'];

$encoding = CharsetHelper::detectBatch($data);
echo "Detected: {$encoding}\n"; // Detected: UTF-8 or ISO-8859-1
```

### Example #2 Detect before batch conversion

```php
<?php
use Ducks\Component\EncodingRepair\CharsetHelper;

$rows = $db->query("SELECT * FROM legacy_table")->fetchAll();

// Detect encoding once
$encoding = CharsetHelper::detectBatch($rows);
echo "Source encoding: {$encoding}\n";

// Use detected encoding for batch conversion
$utf8Rows = CharsetHelper::toCharsetBatch(
    $rows,
    CharsetHelper::ENCODING_UTF8,
    $encoding
);
```

### Example #3 Custom encoding list

```php
<?php
use Ducks\Component\EncodingRepair\CharsetHelper;

$japaneseData = ['こんにちは', '世界'];

$encoding = CharsetHelper::detectBatch($japaneseData, [
    'encodings' => ['UTF-8', 'Shift_JIS', 'EUC-JP', 'ISO-2022-JP']
]);

echo "Detected: {$encoding}\n";
```

### Example #4 Iterator support

```php
<?php
use Ducks\Component\EncodingRepair\CharsetHelper;

function generateData(): Generator {
    yield 'Café';
    yield 'résumé';
    yield 'naïve';
}

// Works with any iterable
$encoding = CharsetHelper::detectBatch(generateData());
echo "Detected: {$encoding}\n";
```

## Notes

!!! warning
    This method only examines the first non-empty string in the collection. For mixed encodings, you may need to detect each item individually.

!!! warning "Detection Accuracy"
    Detection is not 100% accurate, especially for short strings or similar encodings (e.g., UTF-8 vs ISO-8859-1). Use with caution for critical applications.

!!! tip
    The method accepts any iterable (arrays, iterators, generators), making it flexible for various data sources.

## See Also

- [CharsetHelper::detect](CharsetHelper.detect.md) — Detect encoding of a single string
- [CharsetHelper::toCharsetBatch](CharsetHelper.toCharsetBatch.md) — Batch convert with detection
- [CharsetProcessor::detectBatch](CharsetProcessor.md) — Service implementation
- [mb_detect_encoding()](https://www.php.net/manual/en/function.mb-detect-encoding.php) — Detect character encoding
