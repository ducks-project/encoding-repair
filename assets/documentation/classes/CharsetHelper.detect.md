# <a name="charsethelper__detect"></a>[CharsetHelper::detect](#charsethelper__detect)

(PHP 7 >= 7.4.0, PHP 8)

CharsetHelper::detect — Detect charset encoding of a string

## [Description](#description)

```php
public static CharsetHelper::detect(string $string, array $options = []): string
```

Automatically detects the character encoding of a string using multiple detection
strategies with fallback (mb_detect_encoding → FileInfo).

## [Parameters](#parameters)

**string**:

The string to analyze.

**options**:

Optional array of detection options:

- `encodings` (array): List of encodings to test
(default: ['UTF-8', 'CP1252', 'ISO-8859-1', 'ASCII'])

## [Return Values](#return-values)

Returns the detected encoding as an uppercase string (e.g., 'UTF-8', 'ISO-8859-1').

## [Examples](#examples)

### Example #1 Basic encoding detection

```php
<?php
use Ducks\Component\Component\EncodingRepair\CharsetHelper;

$string = file_get_contents('unknown.txt');
$encoding = CharsetHelper::detect($string);
echo "Detected: {$encoding}";
```

### Example #2 Custom encoding list

```php
<?php
use Ducks\Component\Component\EncodingRepair\CharsetHelper;

$encoding = CharsetHelper::detect($string, [
    'encodings' => ['UTF-8', 'Shift_JIS', 'EUC-JP']
]);
```

## [See Also](#see-also)

- [CharsetHelper::toCharset] — Convert with auto-detection
- [mb_detect_encoding()] — PHP native detection function

[CharsetHelper::toCharset]: ./CharsetHelper.toCharset.md#charsethelper__tocharset
[mb_detect_encoding()]: https://www.php.net/manual/en/function.mb-detect-encoding.php
