# CharsetHelper::toCharsetBatch

(PHP 7 >= 7.4.0, PHP 8)

CharsetHelper::toCharsetBatch — Batch convert array items from one encoding to another

## Description

```php
public static CharsetHelper::toCharsetBatch(
    array $items,
    string $to = CharsetHelper::ENCODING_UTF8,
    string $from = CharsetHelper::ENCODING_ISO,
    array $options = []
): array
```

Optimized batch conversion for homogeneous arrays. Detects encoding once on the first non-empty string when using AUTO detection, then applies the same encoding to all items. This provides **40-60% performance improvement** over calling `toCharset()` on each item individually.

## Parameters

**items**

Array of items to convert. Each item will be processed recursively (strings, arrays, objects).

**to**

Target encoding. Use one of the CharsetHelper encoding constants (ENCODING_UTF8, ENCODING_ISO, etc.).

**from**

Source encoding. Use `CharsetHelper::AUTO` for automatic detection (recommended for batch processing).

**options**

Optional array of conversion options:

- `normalize` (bool, default: true): Apply Unicode NFC normalization
- `translit` (bool, default: true): Transliterate unavailable characters
- `ignore` (bool, default: true): Ignore invalid sequences
- `encodings` (array): List of encodings to try during detection

## Return Values

Returns an array with all items converted to the target encoding.

## Examples

### Example #1 Batch conversion with AUTO detection

```php
<?php
use Ducks\Component\EncodingRepair\CharsetHelper;

// Database rows with mixed encoding
$rows = $db->query("SELECT * FROM users")->fetchAll();

// Single detection for entire batch (fast!)
$utf8Rows = CharsetHelper::toCharsetBatch(
    $rows,
    CharsetHelper::ENCODING_UTF8,
    CharsetHelper::AUTO
);

foreach ($utf8Rows as $row) {
    echo $row['name'] . "\n";
}
```

### Example #2 Performance comparison

```php
<?php
use Ducks\Component\EncodingRepair\CharsetHelper;

$items = array_fill(0, 1000, 'Café résumé');

// Slow: 1000 detections
$start = microtime(true);
$result1 = array_map(
    fn($item) => CharsetHelper::toUtf8($item, CharsetHelper::AUTO),
    $items
);
$time1 = microtime(true) - $start;

// Fast: 1 detection
$start = microtime(true);
$result2 = CharsetHelper::toCharsetBatch(
    $items,
    CharsetHelper::ENCODING_UTF8,
    CharsetHelper::AUTO
);
$time2 = microtime(true) - $start;

echo "Regular: {$time1}s\n";
echo "Batch: {$time2}s\n";
echo "Speedup: " . round($time1 / $time2, 2) . "x\n";
```

### Example #3 CSV import

```php
<?php
use Ducks\Component\EncodingRepair\CharsetHelper;

$csv = array_map('str_getcsv', file('data.csv'));

// Convert all CSV rows to UTF-8
$utf8Csv = CharsetHelper::toCharsetBatch(
    $csv,
    CharsetHelper::ENCODING_UTF8,
    CharsetHelper::AUTO
);

// Process clean UTF-8 data
foreach ($utf8Csv as $row) {
    processRow($row);
}
```

## Notes

!!! note
    This method is optimized for homogeneous arrays where all items have the same encoding. For mixed encodings, use `toCharset()` on individual items.

!!! note
    When using AUTO detection, the encoding is detected from the first non-empty string in the array. Empty strings and non-string values are skipped during detection.

!!! tip "Performance"
    Use this method when processing large arrays (> 100 items) with AUTO detection for maximum performance gains.

## See Also

- [CharsetHelper::toCharset](CharsetHelper.toCharset.md) — Convert individual data
- [CharsetHelper::detectBatch](CharsetHelper.detectBatch.md) — Detect encoding from array
- [CharsetProcessor::toCharsetBatch](CharsetProcessor.md) — Service implementation
