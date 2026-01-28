# CharsetHelper::is()

## Description

Checks if a string is encoded in the specified encoding.

## Signature

```php
public static function is(string $string, string $encoding, array $options = []): bool
```

## Parameters

- **`$string`** (string): String to check
- **`$encoding`** (string): Expected encoding (e.g., 'UTF-8', 'ISO-8859-1', 'CP1252')
- **`$options`** (array, optional): Detection options
  - `'encodings'`: array of encodings to test during detection

## Return Value

Returns `true` if the string matches the specified encoding, `false` otherwise.

## Exceptions

- **`InvalidArgumentException`**: If the encoding is not in the allowed encodings list

## How It Works

1. Validates the encoding against the whitelist
2. Uses the DetectorChain to detect the actual encoding of the string
3. Compares the detected encoding with the expected encoding
4. Handles encoding aliases (e.g., CP1252 = ISO-8859-1 = Windows-1252)
5. Normalizes encoding names (case-insensitive comparison)

## Examples

### Basic Usage

```php
use Ducks\Component\EncodingRepair\CharsetHelper;

// Check if string is UTF-8
$utf8String = 'Café résumé';
if (CharsetHelper::is($utf8String, 'UTF-8')) {
    echo "String is valid UTF-8";
}

// Check if string is ISO-8859-1
$isoString = mb_convert_encoding('Café', 'ISO-8859-1', 'UTF-8');
if (CharsetHelper::is($isoString, 'ISO-8859-1')) {
    echo "String is ISO-8859-1";
}
```

### Conditional Conversion

```php
// Avoid unnecessary conversions
$data = 'São Paulo';
if (!CharsetHelper::is($data, 'UTF-8')) {
    $data = CharsetHelper::toUtf8($data);
}
```

### Encoding Aliases

```php
// CP1252 and ISO-8859-1 are treated as aliases
$cp1252String = mb_convert_encoding('€', 'CP1252', 'UTF-8');

CharsetHelper::is($cp1252String, 'CP1252');      // true
CharsetHelper::is($cp1252String, 'ISO-8859-1');  // true (alias)
CharsetHelper::is($cp1252String, 'UTF-8');       // false
```

### Case-Insensitive

```php
// Encoding names are case-insensitive
CharsetHelper::is('test', 'utf-8');  // true
CharsetHelper::is('test', 'UTF-8');  // true
CharsetHelper::is('test', 'Utf-8');  // true
```

### Database Validation

```php
// Validate before database insert
$userInput = 'Gérard Müller';
if (CharsetHelper::is($userInput, 'UTF-8')) {
    $db->insert('users', ['name' => $userInput]);
} else {
    $userInput = CharsetHelper::toUtf8($userInput, CharsetHelper::AUTO);
    $db->insert('users', ['name' => $userInput]);
}
```

### API Response Validation

```php
// Validate all data before JSON encoding
$apiData = ['name' => 'José', 'city' => 'São Paulo'];
$allUtf8 = true;

foreach ($apiData as $value) {
    if (!is_string($value) || !CharsetHelper::is($value, 'UTF-8')) {
        $allUtf8 = false;
        break;
    }
}

if ($allUtf8) {
    $json = json_encode($apiData);
} else {
    $apiData = CharsetHelper::toUtf8($apiData, CharsetHelper::AUTO);
    $json = json_encode($apiData);
}
```

### Batch Validation

```php
// Validate multiple strings
$strings = ['Café', 'Thé', 'Crème'];
$allUtf8 = true;

foreach ($strings as $string) {
    if (!CharsetHelper::is($string, 'UTF-8')) {
        $allUtf8 = false;
        break;
    }
}
```

## Performance Considerations

- **Fast-path for UTF-8**: Uses optimized ASCII detection (~35% faster)
- **Cached detection**: Benefits from PSR-16 cache if enabled (50-80% faster)
- **Avoid unnecessary conversions**: Use `is()` to check before converting

## Use Cases

1. **Input Validation**: Verify user input encoding before processing
2. **Database Operations**: Ensure data matches database charset
3. **API Responses**: Validate encoding before JSON serialization
4. **File Processing**: Check file encoding before parsing
5. **Migration Scripts**: Verify encoding during data migration
6. **Performance Optimization**: Skip conversion if already in target encoding

## Related Methods

- [`detect()`](CharsetHelper.detect.md) - Detect string encoding
- [`toCharset()`](CharsetHelper.toCharset.md) - Convert to specific encoding
- [`toUtf8()`](CharsetHelper.toUtf8.md) - Convert to UTF-8

## See Also

- [CharsetHelper](CharsetHelper.md) - Main class documentation
- [CharsetProcessor](CharsetProcessor.md) - Service implementation
- [DetectorChain](DetectorChain.md) - Detection chain implementation
