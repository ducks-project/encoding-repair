# <a name="charsethelper__toutf8"></a>[CharsetHelper::toUtf8](#charsethelper__toutf8)

(PHP 7 >= 7.4.0, PHP 8)

CharsetHelper::toUtf8 — Convert data to UTF-8

## [Description](#description)

```php
public static CharsetHelper::toUtf8(
    mixed $data,
    string $from = CharsetHelper::WINDOWS_1252,
    array $options = []
): mixed
```

Convenience method to convert data to UTF-8 encoding.
Recursively processes strings, arrays, and objects.

## [Parameters](#parameters)

**data**:

The data to convert. Can be a string, array, or object.

**from**:

Source encoding. Defaults to Windows-1252 (CP1252)
which is more common than strict ISO-8859-1.

**options**:

Optional array of conversion options (see CharsetHelper::toCharset for details).

## [Return Values](#return-values)

Returns the data converted to UTF-8 in the same type as the input.

## [Examples](#examples)

### Example #1 Convert Latin-1 string to UTF-8

```php
<?php
use Ducks\Component\Component\EncodingRepair\CharsetHelper;

$latin = "Café résumé";
$utf8 = CharsetHelper::toUtf8($latin, CharsetHelper::ENCODING_ISO);
echo $utf8; // Café résumé (UTF-8)
```

### Example #2 Database migration

```php
<?php
use Ducks\Component\Component\EncodingRepair\CharsetHelper;

$users = $db->query("SELECT * FROM users")->fetchAll();
foreach ($users as $user) {
    $user = CharsetHelper::toUtf8($user, CharsetHelper::ENCODING_ISO);
    $db->update('users', $user, ['id' => $user['id']]);
}
```

## [See Also](#see-also)

- [CharsetHelper::toCharset] — Convert data to any encoding
- [CharsetHelper::toIso] — Convert data to ISO-8859-1

[CharsetHelper::toCharset]: ./CharsetHelper.toCharset.md#CharsetHelper::toCharset
[CharsetHelper::toIso]: ./CharsetHelper.toIso.md#CharsetHelper::toIso
