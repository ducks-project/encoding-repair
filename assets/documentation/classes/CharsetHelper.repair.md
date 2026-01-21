# <a name="charsethelper__repair"></a>[CharsetHelper::repair](#charsethelper__repair)

(PHP 7 >= 7.4.0, PHP 8)

CharsetHelper::repair — Repair double-encoded strings

## [Description](#description)

```php
public static CharsetHelper::repair(
    mixed $data,
    string $to = CharsetHelper::ENCODING_UTF8,
    string $from = CharsetHelper::ENCODING_ISO,
    array $options = []
): mixed
```

Repairs strings that have been encoded multiple times by detecting and reversing
the encoding layers.
Common with legacy databases where UTF-8 data was misinterpreted as ISO-8859-1
and re-encoded.

## [Parameters](#parameters)

**data**:

The data to repair. Can be a string, array, or object.

**to**:

Target encoding after repair. Defaults to UTF-8.

**from**:

The "glitch" encoding that caused the double encoding. Usually ISO-8859-1 or Windows-1252.

**options**:

Optional array of repair options:

- `maxDepth` (int, default: 5): Maximum encoding layers to peel
- `normalize` (bool, default: true): Apply Unicode normalization
- `translit` (bool, default: true): Transliterate unmappable characters
- `ignore` (bool, default: true): Skip invalid sequences

## [Return Values](#return-values)

Returns the repaired data in the same type as the input.

## [Examples](#examples)

### Example #1 Repair double-encoded string

```php
<?php
use Ducks\Component\Component\EncodingRepair\CharsetHelper;

// UTF-8 "Café" interpreted as ISO-8859-1, then re-encoded as UTF-8
$corrupted = "CafÃ©";
$fixed = CharsetHelper::repair($corrupted);
echo $fixed; // Café
```

### Example #2 Deep repair with custom depth

```php
<?php
use Ducks\Component\Component\EncodingRepair\CharsetHelper;

$deeplyCorrupted = "CafÃÂ©";
$fixed = CharsetHelper::repair(
    $deeplyCorrupted,
    CharsetHelper::ENCODING_UTF8,
    CharsetHelper::ENCODING_ISO,
    ['maxDepth' => 10]
);
```

### Example #3 Repair legacy database data

```php
<?php
use Ducks\Component\Component\EncodingRepair\CharsetHelper;

$legacyData = $oldSystem->getData();
$clean = CharsetHelper::repair($legacyData);
processData($clean);
```

## [Notes](#notes)

The repair process:

1. Detects valid UTF-8 strings
2. Attempts to reverse-convert (UTF-8 → source encoding)
3. Repeats until no more layers found or max depth reached
4. Converts to target encoding

## [See Also](#see-also)

- [CharsetHelper::toCharset] — Convert data between encodings
- [CharsetHelper::detect] — Detect encoding

[CharsetHelper::toCharset]: ./CharsetHelper.toCharset.md#CharsetHelper::toCharset
[CharsetHelper::detect]: ./CharsetHelper.detect.md#CharsetHelper::detect
