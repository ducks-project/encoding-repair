# <a name="charsethelper__safejsondecode"></a>[CharsetHelper::safeJsonDecode](#charsethelper__safejsondecode)

(PHP 7 >= 7.4.0, PHP 8)

CharsetHelper::safeJsonDecode — JSON decode with charset conversion

## [Description](#description)

```php
public static CharsetHelper::safeJsonDecode(
    string $json,
    ?bool $associative = null,
    int $depth = 512,
    int $flags = 0,
    string $to = CharsetHelper::ENCODING_UTF8,
    string $from = CharsetHelper::WINDOWS_1252
): mixed
```

Safely decodes JSON string with automatic charset repair and conversion.
Repairs the JSON string to valid UTF-8 before decoding,
then converts the result to the target encoding.

## [Parameters](#parameters)

**json**:

The JSON string to decode.

**associative**:

When true, returns associative arrays instead of objects. When null,
uses json_decode default behavior.

**depth**:

Maximum nesting depth. Must be greater than zero.

**flags**:

Bitmask of JSON decode options (same as json_decode).

**to**:

Target encoding for the decoded data. Defaults to UTF-8.

**from**:

Source encoding for repair if JSON contains invalid UTF-8. Defaults to Windows-1252.

## [Return Values](#return-values)

Returns the decoded data in the target encoding. Type depends
on the JSON content and associative parameter.

## <a name="errors-exceptions"></a>[Errors/Exceptions](#errors-exceptions)

Throws RuntimeException if JSON decoding fails.

## [Examples](#examples)

### Example #1 Safe JSON decoding

```php
<?php
use Ducks\Component\Component\EncodingRepair\CharsetHelper;

$json = '{"name":"Gérard","city":"São Paulo"}';
$data = CharsetHelper::safeJsonDecode($json, true);
print_r($data);
```

### Example #2 Decode with charset conversion

```php
<?php
use Ducks\Component\Component\EncodingRepair\CharsetHelper;

$json = file_get_contents('data.json');
$data = CharsetHelper::safeJsonDecode(
    $json,
    true,
    512,
    0,
    CharsetHelper::ENCODING_ISO
);
```

### Example #3 Error handling

```php
<?php
use Ducks\Component\Component\EncodingRepair\CharsetHelper;

try {
    $data = CharsetHelper::safeJsonDecode($json, true);
} catch (RuntimeException $e) {
    echo "JSON Decode Error: " . $e->getMessage();
}
```

## [See Also](#see-also)

- [CharsetHelper::safeJsonEncode] — Safe JSON encoding
- [CharsetHelper::repair] — Repair encoding issues
- [json_decode()] — PHP native JSON decoding

[CharsetHelper::safeJsonEncode]: ./CharsetHelper.safeJsonEncode.md#charsethelper__safejsonencode
[CharsetHelper::repair]: ./CharsetHelper.repair.md#charsethelper__repair
[json_decode()]: https://www.php.net/manual/en/function.json-decode.php
