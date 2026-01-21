# Basic Usage

## Simple String Conversion

```php
use Ducks\Component\EncodingRepair\CharsetHelper;

// Convert ISO-8859-1 to UTF-8
$latin = "Café résumé";
$utf8 = CharsetHelper::toUtf8($latin, CharsetHelper::ENCODING_ISO);

// Convert UTF-8 to Windows-1252
$utf8 = "Café résumé";
$iso = CharsetHelper::toIso($utf8);
```

## Array Conversion

```php
$data = [
    'name' => 'José',
    'city' => 'São Paulo',
    'description' => 'Développeur'
];

$utf8Data = CharsetHelper::toUtf8($data, CharsetHelper::ENCODING_ISO);
```

## Object Conversion

```php
class User {
    public $name;
    public $email;
    public $address;
}

$user = new User();
$user->name = 'José García';
$user->email = 'jose@example.com';

// Returns a cloned object with converted properties
$utf8User = CharsetHelper::toUtf8($user, CharsetHelper::ENCODING_ISO);
```

## Supported Encodings

- `UTF-8`
- `UTF-16`
- `UTF-32`
- `ISO-8859-1`
- `Windows-1252` (CP1252)
- `ASCII`
- `AUTO` (automatic detection)

## Conversion Options

```php
$result = CharsetHelper::toCharset($data, 'UTF-8', 'ISO-8859-1', [
    'normalize' => true,   // Apply Unicode NFC normalization (default: true)
    'translit' => true,    // Transliterate unavailable chars (default: true)
    'ignore' => true,      // Ignore invalid sequences (default: true)
    'encodings' => ['UTF-8', 'ISO-8859-1', 'Shift_JIS']  // For detection
]);
```

**Options explained:**

- `normalize`: Applies Unicode NFC normalization to UTF-8 output (combines accents)
- `translit`: Converts unmappable characters to similar ones (é → e)
- `ignore`: Skips invalid byte sequences instead of failing
- `encodings`: List of encodings to try during auto-detection

## Next Steps

- [Advanced Usage](advanced-usage.md)
- [Use Cases](use-cases.md)
- [API Reference](../api/CharsetHelper.md)
