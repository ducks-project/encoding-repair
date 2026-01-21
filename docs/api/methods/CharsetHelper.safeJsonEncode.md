# <a name="charsethelper__safejsonencode"></a>[CharsetHelper::safeJsonEncode](#charsethelper__safejsonencode)

(PHP 7 >= 7.4.0, PHP 8)

CharsetHelper::safeJsonEncode — JSON encode with automatic charset repair

## [Description](#description)

```php
public static CharsetHelper::safeJsonEncode(
    mixed $data,
    int $flags = 0,
    int $depth = 512,
    string $from = CharsetHelper::WINDOWS_1252
): string
```

Safely encodes data to JSON by automatically repairing charset issues before encoding.
Prevents json_encode from returning false due to invalid UTF-8 sequences.

## [Parameters](#parameters)

**data**:

The data to encode. Can be any type supported by json_encode.

**flags**:

Bitmask of JSON encode options (same as json_encode).

**depth**:

Maximum depth. Must be greater than zero.

**from**:

Source encoding for repair if data contains invalid UTF-8. Defaults to Windows-1252.

## [Return Values](#return-values)

Returns a JSON encoded string.

## <a name="errors-exceptions"></a>[Errors/Exceptions](#errors-exceptions)

Throws RuntimeException if JSON encoding fails after repair.

## [Examples](#examples)

### Example #1 Safe JSON encoding

```php
<?php
use Ducks\Component\Component\EncodingRepair\CharsetHelper;

$data = [
    'name' => 'Gérard',
    'description' => 'Développeur'
];

$json = CharsetHelper::safeJsonEncode($data);
echo $json; // {"name":"Gérard","description":"Développeur"}
```

### Example #2 API response with encoding safety

```php
<?php
use Ducks\Component\Component\EncodingRepair\CharsetHelper;

class ApiController {
    public function jsonResponse($data): JsonResponse {
        $json = CharsetHelper::safeJsonEncode($data, JSON_PRETTY_PRINT);
        return new JsonResponse($json, 200, [], true);
    }
}
```

### Example #3 Error handling

```php
<?php
use Ducks\Component\Component\EncodingRepair\CharsetHelper;

try {
    $json = CharsetHelper::safeJsonEncode($data);
} catch (RuntimeException $e) {
    echo "JSON Error: " . $e->getMessage();
}
```

## [See Also](#see-also)

- [CharsetHelper::safeJsonDecode] — Safe JSON decoding
- [CharsetHelper::repair] — Repair encoding issues
- [json_encode()] — PHP native JSON encoding

[CharsetHelper::safeJsonDecode]: ./CharsetHelper.safeJsonDecode.md#CharsetHelper::safeJsonDecode
[CharsetHelper::repair]: ./CharsetHelper.repair.md#CharsetHelper::repair
[json_encode()]: https://www.php.net/manual/en/function.json-encode.php
