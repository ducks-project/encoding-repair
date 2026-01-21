# <a name="charsethelper__tocharset"></a>[CharsetHelper::toCharset](#charsethelper__tocharset)

(PHP 7 >= 7.4.0, PHP 8)

CharsetHelper::toCharset — Convert data from one encoding to another

## [Description](#description)

```php
public static CharsetHelper::toCharset(
    mixed $data,
    string $to = CharsetHelper::ENCODING_UTF8,
    string $from = CharsetHelper::ENCODING_ISO,
    array $options = []
): mixed
```

Converts data (string, array, or object) from one character encoding to another.
This method recursively processes arrays and objects,
converting all string values while preserving the data structure.

## [Parameters](#parameters)

**data**:

The data to convert. Can be a string, array, or object.
Arrays and objects are processed recursively.

**to**:

Target encoding. Use one of the CharsetHelper encoding constants (e.g., CharsetHelper::ENCODING_UTF8).

**from**:

Source encoding. Use CharsetHelper::AUTO for automatic detection,
or specify an encoding constant.

**options**:

Optional array of conversion options:

- `normalize` (bool, default: true): Apply Unicode NFC normalization
- `translit` (bool, default: true): Transliterate unmappable characters
- `ignore` (bool, default: true): Skip invalid byte sequences
- `encodings` (array): List of encodings for auto-detection

## [Return Values](#return-values)

Returns the converted data in the same type as the input (string, array, or object).

## <a name="errors-exceptions"></a>[Errors/Exceptions](#errors-exceptions)

Throws InvalidArgumentException if the encoding is not in the allowed list.

## [Examples](#examples)

### Example #1 Basic string conversion

```php
<?php
use Ducks\Component\Component\EncodingRepair\CharsetHelper;

$latin = "Café";
$utf8 = CharsetHelper::toCharset($latin, CharsetHelper::ENCODING_UTF8, CharsetHelper::ENCODING_ISO);
echo $utf8; // Café (UTF-8)
```

### Example #2 Array conversion with auto-detection

```php
<?php
use Ducks\Component\Component\EncodingRepair\CharsetHelper;

$data = ['name' => 'José', 'city' => 'São Paulo'];
$utf8Data = CharsetHelper::toCharset($data, CharsetHelper::ENCODING_UTF8, CharsetHelper::AUTO);
print_r($utf8Data);
```

### Example #3 Custom conversion options

```php
<?php
use Ducks\Component\Component\EncodingRepair\CharsetHelper;

$result = CharsetHelper::toCharset($data, 'UTF-8', 'ISO-8859-1', [
    'normalize' => false,
    'translit' => true,
    'ignore' => true
]);
```

## [See Also](#see-also)

- [CharsetHelper::toUtf8] — Convert data to UTF-8
- [CharsetHelper::toIso] — Convert data to ISO-8859-1
- [CharsetHelper::detect] — Detect charset encoding

[CharsetHelper::toUtf8]: ./CharsetHelper.toUtf8.md#CharsetHelper::toUtf8
[CharsetHelper::toIso]: ./CharsetHelper.toIso.md#CharsetHelper::toIso
[CharsetHelper::detect]: ./CharsetHelper.detect.md#CharsetHelper::detect
