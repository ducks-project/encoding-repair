# <a name="charsethelper__toiso"></a>[CharsetHelper::toIso](#charsethelper__toiso)

(PHP 7 >= 7.4.0, PHP 8)

CharsetHelper::toIso — Convert data to ISO-8859-1/Windows-1252

## [Description](#description)

```php
public static CharsetHelper::toIso(
    mixed $data,
    string $from = CharsetHelper::ENCODING_UTF8,
    array $options = []
): mixed
```

Convenience method to convert data to Windows-1252 (CP1252) encoding,
which is a superset of ISO-8859-1 with additional characters.

## [Parameters](#parameters)

**data**:

The data to convert. Can be a string, array, or object.

**from**:

Source encoding. Defaults to UTF-8.

**options**:

Optional array of conversion options (see CharsetHelper::toCharset for details).

## [Return Values](#return-values)

Returns the data converted to Windows-1252 in the same type as the input.

## [Examples](#examples)

### Example #1 Convert UTF-8 to Windows-1252

```php
<?php
use Ducks\Component\Component\EncodingRepair\CharsetHelper;

$utf8 = "Café résumé";
$iso = CharsetHelper::toIso($utf8);
echo bin2hex($iso); // Shows Windows-1252 bytes
```

## [See Also](#see-also)

- [CharsetHelper::toUtf8] — Convert data to UTF-8
- [CharsetHelper::toCharset] — Convert data to any encoding

[CharsetHelper::toUtf8]: ./CharsetHelper.toUtf8.md#CharsetHelper::toUtf8
[CharsetHelper::toCharset]: ./CharsetHelper.toCharset.md#CharsetHelper::toCharset
