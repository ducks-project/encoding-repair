# Usage Guide

Complete guide for using CharsetHelper in your projects.

## Basic Conversion

### Convert to UTF-8

```php
use Ducks\Component\EncodingRepair\CharsetHelper;

$data = [
    'name' => 'Gérard', // ISO-8859-1 string
    'meta' => ['desc' => 'Ca coûte 10€'] // Nested array with Euro sign
];

// Convert to UTF-8
$utf8 = CharsetHelper::toUtf8($data, CharsetHelper::WINDOWS_1252);
```

### Convert to ISO-8859-1

```php
// Convert to ISO-8859-1 (Windows-1252)
$iso = CharsetHelper::toIso($data, CharsetHelper::ENCODING_UTF8);
```

### Convert to Any Encoding

```php
// Convert to any encoding
$result = CharsetHelper::toCharset(
    $data,
    CharsetHelper::ENCODING_UTF16,
    CharsetHelper::ENCODING_UTF8
);
```

### Supported Encodings

- `UTF-8`
- `UTF-16`
- `UTF-32`
- `ISO-8859-1`
- `Windows-1252` (CP1252)
- `ASCII`
- `AUTO` (automatic detection)

> **Note**: We use Windows-1252 instead of strict ISO-8859-1 by default because it includes common characters like €, œ, ™ which are missing in standard ISO.

## Automatic Encoding Detection

### Auto-Detection

Let CharsetHelper detect the source encoding:

```php
// Automatic detection
$result = CharsetHelper::toCharset(
    $unknownData,
    CharsetHelper::ENCODING_UTF8,
    CharsetHelper::AUTO  // Will auto-detect source encoding
);
```

### Manual Detection

```php
// Manual detection
$encoding = CharsetHelper::detect($string);
echo $encoding; // "UTF-8", "ISO-8859-1", etc.
```

### Batch Detection

```php
// Batch detection from array (faster for large datasets)
$encoding = CharsetHelper::detectBatch($items);
```

### Custom Encoding List

```php
// With custom encoding list
$encoding = CharsetHelper::detect($string, [
    'encodings' => ['UTF-8', 'Shift_JIS', 'EUC-JP']
]);
```

## Batch Processing

Optimized for processing large arrays with single encoding detection (40-60% faster):

### Database Migration

```php
// Database migration with batch processing
$rows = $db->query("SELECT * FROM users")->fetchAll(); // 10,000 rows

// Slow: Detects encoding for each row (10,000 detections)
$utf8Rows = array_map(
    fn($row) => CharsetHelper::toUtf8($row, CharsetHelper::AUTO),
    $rows
);

// Fast: Detects encoding once (1 detection, 40-60% faster!)
$utf8Rows = CharsetHelper::toCharsetBatch(
    $rows,
    CharsetHelper::ENCODING_UTF8,
    CharsetHelper::AUTO
);
```

### CSV Import

```php
// CSV import example
$csvData = array_map('str_getcsv', file('data.csv'));
$utf8Csv = CharsetHelper::toCharsetBatch($csvData, 'UTF-8', CharsetHelper::AUTO);
```

## Recursive Conversion

### Array Conversion

Convert nested data structures:

```php
// Array conversion
$data = [
    'name' => 'Café',
    'city' => 'São Paulo',
    'items' => [
        'entrée' => 'Crème brûlée',
        'plat' => 'Bœuf bourguignon'
    ]
];

$utf8Data = CharsetHelper::toUtf8($data, CharsetHelper::WINDOWS_1252);
```

### Object Conversion

```php
// Object conversion
class User {
    public $name;
    public $email;
}

$user = new User();
$user->name = 'José';
$user->email = 'josé@example.com';

$utf8User = CharsetHelper::toUtf8($user, CharsetHelper::ENCODING_ISO);
// Returns a cloned object with converted properties
```

## Double-Encoding Repair

Fix strings that have been encoded multiple times (common with legacy databases):

### Basic Repair

```php
// Example: "CafÃ©" (UTF-8 interpreted as ISO, then re-encoded as UTF-8)
$corrupted = "CafÃ©";

$fixed = CharsetHelper::repair($corrupted);
echo $fixed; // "Café"
```

### Custom Max Depth

```php
// With custom max depth
$fixed = CharsetHelper::repair(
    $corrupted,
    CharsetHelper::ENCODING_UTF8,
    CharsetHelper::ENCODING_ISO,
    ['maxDepth' => 10]  // Try to peel up to 10 encoding layers
);
```

### How It Works

1. Detects valid UTF-8 strings
2. Attempts to reverse-convert (UTF-8 → source encoding)
3. Repeats until no more layers found or max depth reached
4. Returns the cleaned string

## Safe JSON Operations

Prevent JSON encoding/decoding errors caused by invalid UTF-8:

### Safe Encoding

```php
// Safe encoding (auto-repairs before encoding)
$json = CharsetHelper::safeJsonEncode($data);
```

### Safe Decoding

```php
// Safe decoding with charset conversion
$data = CharsetHelper::safeJsonDecode(
    $json,
    true,  // associative array
    512,   // depth
    0,     // flags
    CharsetHelper::ENCODING_UTF8,      // target encoding
    CharsetHelper::WINDOWS_1252        // source encoding for repair
);
```

### Error Handling

```php
// Throws RuntimeException on error with clear message
try {
    $json = CharsetHelper::safeJsonEncode($invalidData);
} catch (RuntimeException $e) {
    echo $e->getMessage();
    // "JSON Encode Error: Malformed UTF-8 characters"
}
```

## Conversion Options

Fine-tune the conversion behavior:

### Available Options

```php
$result = CharsetHelper::toCharset($data, 'UTF-8', 'ISO-8859-1', [
    'normalize' => true,   // Apply Unicode NFC normalization (default: true)
    'translit' => true,    // Transliterate unavailable chars (default: true)
    'ignore' => true,      // Ignore invalid sequences (default: true)
    'encodings' => ['UTF-8', 'ISO-8859-1', 'Shift_JIS']  // For detection
]);
```

### Options Explained

- **normalize**: Applies Unicode NFC normalization to UTF-8 output (combines accents)
- **translit**: Converts unmappable characters to similar ones (é → e)
- **ignore**: Skips invalid byte sequences instead of failing
- **encodings**: List of encodings to try during auto-detection

### Examples

```php
// Disable normalization
$result = CharsetHelper::toUtf8($data, 'ISO-8859-1', ['normalize' => false]);

// Disable transliteration
$result = CharsetHelper::toUtf8($data, 'ISO-8859-1', ['translit' => false]);

// Strict mode (fail on invalid sequences)
$result = CharsetHelper::toUtf8($data, 'ISO-8859-1', ['ignore' => false]);

// Custom encoding list for detection
$result = CharsetHelper::toUtf8($data, CharsetHelper::AUTO, [
    'encodings' => ['UTF-8', 'SHIFT_JIS', 'EUC-JP', 'GB2312']
]);
```

## Checking Encoding

### Check if Data is UTF-8

```php
// Check encoding before conversion
if (!CharsetHelper::is($data, 'UTF-8')) {
    $data = CharsetHelper::toUtf8($data, CharsetHelper::AUTO);
}
```

### Validate Encoding

```php
// Validate specific encoding
if (CharsetHelper::is($string, 'ISO-8859-1')) {
    echo "String is ISO-8859-1 encoded";
}
```

## Working with Different Data Types

### Strings

```php
$string = 'Café';
$utf8String = CharsetHelper::toUtf8($string, 'ISO-8859-1');
```

### Arrays

```php
$array = ['name' => 'José', 'city' => 'São Paulo'];
$utf8Array = CharsetHelper::toUtf8($array, 'ISO-8859-1');
```

### Objects

```php
$object = new stdClass();
$object->name = 'José';
$utf8Object = CharsetHelper::toUtf8($object, 'ISO-8859-1');
```

### Mixed Data

```php
$mixed = [
    'string' => 'Café',
    'array' => ['name' => 'José'],
    'object' => (object)['city' => 'São Paulo']
];
$utf8Mixed = CharsetHelper::toUtf8($mixed, 'ISO-8859-1');
```

## Common Patterns

### Database to UTF-8

```php
// Convert database results to UTF-8
$users = $db->query("SELECT * FROM users")->fetchAll();
$utf8Users = CharsetHelper::toCharsetBatch($users, 'UTF-8', 'WINDOWS-1252');
```

### File to UTF-8

```php
// Convert file content to UTF-8
$content = file_get_contents('data.txt');
$utf8Content = CharsetHelper::toUtf8($content, CharsetHelper::AUTO);
file_put_contents('data_utf8.txt', $utf8Content);
```

### API Response to UTF-8

```php
// Ensure API response is UTF-8
$response = $api->getData();
$utf8Response = CharsetHelper::toUtf8($response, CharsetHelper::AUTO);
return json_encode($utf8Response);
```

### Form Data to UTF-8

```php
// Convert form data to UTF-8
$formData = $_POST;
$utf8FormData = CharsetHelper::toUtf8($formData, CharsetHelper::AUTO);
```

## Performance Tips

### Use Specific Encodings

```php
// Faster: Specific encoding
$result = CharsetHelper::toUtf8($data, 'ISO-8859-1');

// Slower: Auto-detection
$result = CharsetHelper::toUtf8($data, CharsetHelper::AUTO);
```

### Use Batch Methods

```php
// Faster: Batch processing (single detection)
$results = CharsetHelper::toCharsetBatch($items, 'UTF-8', CharsetHelper::AUTO);

// Slower: Individual processing (multiple detections)
$results = array_map(
    fn($item) => CharsetHelper::toUtf8($item, CharsetHelper::AUTO),
    $items
);
```

### Install ext-intl

```bash
# Ubuntu/Debian
sudo apt-get install php-intl

# macOS
brew install php@8.2  # Includes intl

# Windows: Enable in php.ini
extension=intl
```

UConverter (ext-intl) is 30% faster than other methods.

## Error Handling

### Catching Exceptions

```php
use InvalidArgumentException;
use RuntimeException;

try {
    $result = CharsetHelper::toCharset($data, 'INVALID-ENCODING', 'UTF-8');
} catch (InvalidArgumentException $e) {
    // Invalid encoding specified
    echo "Error: " . $e->getMessage();
}

try {
    $json = CharsetHelper::safeJsonEncode($data);
} catch (RuntimeException $e) {
    // JSON encoding failed
    echo "Error: " . $e->getMessage();
}
```

### Validation Before Conversion

```php
// Validate encoding exists
$allowedEncodings = ['UTF-8', 'ISO-8859-1', 'WINDOWS-1252'];
if (in_array($sourceEncoding, $allowedEncodings)) {
    $result = CharsetHelper::toUtf8($data, $sourceEncoding);
}
```
