# [The CharsetHelper class](#the-charsethelper-class)

(PHP 7 >= 7.4.0, PHP 8)

## [Introduction](#introduction)

CharsetHelper is an advanced charset encoding converter that implements
the Chain of Responsibility pattern for extensible and robust character
encoding conversion.
It provides automatic encoding detection, double-encoding repair capabilities,
and safe JSON operations with UTF-8 compliance.

Unlike existing libraries, CharsetHelper offers multiple fallback strategies
(UConverter → iconv → mbstring), recursive conversion for arrays and objects,
and the ability to repair corrupted double-encoded legacy data
commonly found in old databases.

## [Class synopsis](#class-synopsis)

```php
final class CharsetHelper {
    /* Constants */
    public const string AUTO = 'AUTO';
    public const string ENCODING_UTF8 = 'UTF-8';
    public const string ENCODING_UTF16 = 'UTF-16';
    public const string ENCODING_UTF32 = 'UTF-32';
    public const string ENCODING_ISO = 'ISO-8859-1';
    public const string WINDOWS_1252 = 'CP1252';
    public const string ENCODING_ASCII = 'ASCII';

    /* Methods */
    public static toCharset(
        mixed $data,
        string $to = CharsetHelper::ENCODING_UTF8,
        string $from = CharsetHelper::ENCODING_ISO,
        array $options = []
    ): mixed

    public static toUtf8(
        mixed $data,
        string $from = CharsetHelper::WINDOWS_1252,
        array $options = []
    ): mixed

    public static toIso(
        mixed $data,
        string $from = CharsetHelper::ENCODING_UTF8,
        array $options = []
    ): mixed

    public static detect(string $string, array $options = []): string

    public static repair(
        mixed $data,
        string $to = CharsetHelper::ENCODING_UTF8,
        string $from = CharsetHelper::ENCODING_ISO,
        array $options = []
    ): mixed

    public static safeJsonEncode(
        mixed $data,
        int $flags = 0,
        int $depth = 512,
        string $from = CharsetHelper::WINDOWS_1252
    ): string

    public static safeJsonDecode(
        string $json,
        ?bool $associative = null,
        int $depth = 512,
        int $flags = 0,
        string $to = CharsetHelper::ENCODING_UTF8,
        string $from = CharsetHelper::WINDOWS_1252
    ): mixed

    public static registerTranscoder(
        string|callable $transcoder,
        bool $prepend = true
    ): void

    public static registerDetector(
        string|callable $detector,
        bool $prepend = true
    ): void
}
```

## [Predefined Constants](#predefined-constants)

**CharsetHelper::AUTO**:

Constant for automatic encoding detection. When used as source encoding,
CharsetHelper will automatically detect the input encoding.

**CharsetHelper::ENCODING_UTF8**:

UTF-8 encoding constant ('UTF-8').

**CharsetHelper::ENCODING_UTF16**:

UTF-16 encoding constant ('UTF-16').

**CharsetHelper::ENCODING_UTF32**:

UTF-32 encoding constant ('UTF-32').

**CharsetHelper::ENCODING_ISO**:

ISO-8859-1 encoding constant ('ISO-8859-1').

**CharsetHelper::WINDOWS_1252**:

Windows-1252 (CP1252) encoding constant. Preferred over strict ISO-8859-1
as it includes common characters like €, œ, ™.

**CharsetHelper::ENCODING_ASCII**:

ASCII encoding constant ('ASCII').

## [Features](#features)

- **Chain of Responsibility Pattern**: Multiple conversion strategies with
automatic fallback (UConverter → iconv → mbstring)
- **Automatic Encoding Detection**: Smart detection using multiple methods
(mb_detect_encoding, FileInfo)
- **Double-Encoding Repair**: Fixes strings encoded multiple times
(e.g., "CafÃ©" → "Café")
- **Recursive Processing**: Handles strings, arrays, and objects recursively
while preserving structure
- **Immutable Operations**: Objects are cloned before modification to
prevent side effects
- **Safe JSON Operations**: Prevents json_encode failures with automatic
charset repair
- **Extensible Architecture**: Register custom transcoders and detectors
without modifying core
- **Strict Typing**: Full PHP strict types support with comprehensive type declarations

## [Examples](#examples)

### Example #1 Basic UTF-8 conversion

```php
<?php

use Ducks\Component\Component\EncodingRepair\CharsetHelper;

$latinString = "Café résumé";

// Convert to UTF-8
$utf8String = CharsetHelper::toUtf8($latinString, CharsetHelper::ENCODING_ISO);

echo $utf8String; // Café résumé (valid UTF-8)
```

### Example #2 Automatic encoding detection

```php
<?php

use Ducks\Component\Component\EncodingRepair\CharsetHelper;

$unknownData = file_get_contents('legacy-data.txt');

// Auto-detect and convert to UTF-8
$utf8Data = CharsetHelper::toCharset(
    $unknownData,
    CharsetHelper::ENCODING_UTF8,
    CharsetHelper::AUTO
);

// Manual detection
$encoding = CharsetHelper::detect($unknownData);
echo "Detected encoding: {$encoding}";
```

### Example #3 Recursive array conversion

```php
<?php

use Ducks\Component\Component\EncodingRepair\CharsetHelper;

$data = [
    'name' => 'José',
    'city' => 'São Paulo',
    'items' => [
        'entrée' => 'Crème brûlée',
        'plat' => 'Bœuf bourguignon'
    ]
];

// Convert entire array structure to UTF-8
$utf8Data = CharsetHelper::toUtf8($data, CharsetHelper::WINDOWS_1252);

print_r($utf8Data);
```

The above example will output:

> ```text
> Array
> (
>     [name] => José
>     [city] => São Paulo
>     [items] => Array
>         (
>             [entrée] => Crème brûlée
>             [plat] => Bœuf bourguignon
>         )
> )
> ```

### Example #4 Repairing double-encoded strings

```php
<?php

use Ducks\Component\Component\EncodingRepair\CharsetHelper;

// String that was UTF-8, interpreted as ISO, then re-encoded as UTF-8
$corrupted = "CafÃ©";

// Repair the corruption
$fixed = CharsetHelper::repair($corrupted);

echo $fixed; // Café

// With custom max depth for multiple encoding layers
$deeplyCorrupted = "CafÃÂ©";
$fixed = CharsetHelper::repair(
    $deeplyCorrupted,
    CharsetHelper::ENCODING_UTF8,
    CharsetHelper::ENCODING_ISO,
    ['maxDepth' => 10]
);
```

### Example #5 Safe JSON encoding

```php
<?php

use Ducks\Component\Component\EncodingRepair\CharsetHelper;

$data = [
    'name' => 'Gérard',
    'description' => 'Développeur'
];

// Safe JSON encode with automatic charset repair
$json = CharsetHelper::safeJsonEncode($data);

echo $json; // {"name":"Gérard","description":"Développeur"}

// Safe JSON decode with charset conversion
$decoded = CharsetHelper::safeJsonDecode($json, true);
print_r($decoded);
```

### Example #6 Registering custom transcoder

```php
<?php

use Ducks\Component\Component\EncodingRepair\CharsetHelper;

// Register a custom transcoder for a proprietary encoding
CharsetHelper::registerTranscoder(
    function (string $data, string $to, string $from, array $options): ?string {
        if ($from === 'MY-CUSTOM-ENCODING') {
            // Custom conversion logic
            return myCustomConversion($data, $to);
        }

        // Return null to try next transcoder in chain
        return null;
    },
    true  // Prepend (higher priority)
);

// Now use it
$result = CharsetHelper::toCharset($data, 'UTF-8', 'MY-CUSTOM-ENCODING');
```

### Example #7 Database migration

```php
<?php

use Ducks\Component\Component\EncodingRepair\CharsetHelper;

// Migrate user table from Latin1 to UTF-8
$users = $db->query("SELECT * FROM users")->fetchAll();

foreach ($users as $user) {
    $user = CharsetHelper::toUtf8($user, CharsetHelper::ENCODING_ISO);
    $db->update('users', $user, ['id' => $user['id']]);
}
```

### Example #8 Conversion options

```php
<?php

use Ducks\Component\Component\EncodingRepair\CharsetHelper;

$data = "Café résumé";

// Fine-tune conversion behavior
$result = CharsetHelper::toCharset($data, 'UTF-8', 'ISO-8859-1', [
    'normalize' => true,   // Apply Unicode NFC normalization (default: true)
    'translit' => true,    // Transliterate unavailable chars (default: true)
    'ignore' => true,      // Ignore invalid sequences (default: true)
    'encodings' => ['UTF-8', 'ISO-8859-1', 'Shift_JIS']  // For detection
]);
```

## [Conversion Options](#conversion-options)

All conversion methods accept an `$options` array parameter with the following keys:

- **normalize** (bool, default: true): Apply Unicode NFC normalization to UTF-8
output (combines accents)
- **translit** (bool, default: true): Transliterate unmappable characters to
similar ones (é → e)
- **ignore** (bool, default: true): Skip invalid byte sequences instead of failing
- **encodings** (array, default: ['UTF-8', 'CP1252', 'ISO-8859-1', 'ASCII']):
List of encodings to try during auto-detection
- **maxDepth** (int, default: 5): Maximum encoding layers to peel when using
repair() method

## [Chain of Responsibility](#chain-of-responsibility)

CharsetHelper uses multiple conversion strategies with automatic fallback:

```text
UConverter (intl) → iconv → mbstring
     ↓ (fails)         ↓ (fails)    ↓ (always works)
```

**Transcoder priorities:**

1. **UConverter** (requires ext-intl): Best precision, supports many encodings,
~30% faster
2. **iconv**: Good performance, supports transliteration (//TRANSLIT, //IGNORE)
3. **mbstring**: Universal fallback, most permissive, always available

**Detector priorities:**

1. **mb_detect_encoding**: Fast and reliable for common encodings
2. **finfo (FileInfo)**: Fallback for difficult cases

## [Performance](#performance)

Benchmarks on 10,000 conversions (PHP 8.2, i7-12700K):

| Operation | Time | Memory |
| ----------- | ------ | -------- |
| Simple UTF-8 conversion | 45ms | 2MB |
| Array (100 items) | 180ms | 5MB |
| Auto-detection + conversion | 92ms | 3MB |
| Double-encoding repair | 125ms | 4MB |
| Safe JSON encode | 67ms | 3MB |

**Performance tips:**

- Install ext-intl for best performance (UConverter is fastest)
- Use specific encodings instead of AUTO when possible
- Cache detection results for repeated operations

## [Requirements](#requirements)

- **PHP**: 7.4, 8.0, 8.1, 8.2, or 8.3
- **Required Extensions**: ext-mbstring, ext-json
- **Recommended Extensions**: ext-intl (30% performance boost), ext-iconv
(transliteration), ext-fileinfo (advanced detection)

## [Table of Contents](#table-of-contents)

- [CharsetHelper::toCharset] — Convert data from one encoding to another
- [CharsetHelper::toUtf8] — Convert data to UTF-8
- [CharsetHelper::toIso] — Convert data to ISO-8859-1/Windows-1252
- [CharsetHelper::detect] — Detect charset encoding of a string
- [CharsetHelper::repair] — Repair double-encoded strings
- [CharsetHelper::safeJsonEncode] — JSON encode with automatic charset repair
- [CharsetHelper::safeJsonDecode] — JSON decode with charset conversion
- [CharsetHelper::registerTranscoder] — Register custom transcoding strategy
- [CharsetHelper::registerDetector] — Register custom detection strategy

## [See Also](#see-also)

- [mb_convert_encoding()] — Convert character encoding
- [iconv()] — Convert string to requested character encoding
- [mb_detect_encoding()] — Detect character encoding
- [json_encode()] — Returns the JSON representation of a value
- [Normalizer::normalize()] — Normalizes the input provided

[CharsetHelper::toCharset]: ./CharsetHelper.toCharset.md#CharsetHelper::toCharset
[CharsetHelper::toUtf8]: ./CharsetHelper.toUtf8.md#CharsetHelper::toUtf8
[CharsetHelper::toIso]: ./CharsetHelper.toIso.md#CharsetHelper::toIso
[CharsetHelper::detect]: ./CharsetHelper.detect.md#CharsetHelper::detect
[CharsetHelper::repair]: ./CharsetHelper.repair.md#CharsetHelper::repair
[CharsetHelper::safeJsonEncode]: ./CharsetHelper.safeJsonEncode.md#CharsetHelper::safeJsonEncode
[CharsetHelper::safeJsonDecode]: ./CharsetHelper.safeJsonDecode.md#CharsetHelper::safeJsonDecode
[CharsetHelper::registerTranscoder]: ./CharsetHelper.registerTranscoder.md#CharsetHelper::registerTranscoder
[CharsetHelper::registerDetector]: ./CharsetHelper.registerDetector.md#CharsetHelper::registerDetector
[mb_convert_encoding()]: https://www.php.net/manual/en/function.mb-convert-encoding.php
[iconv()]: https://www.php.net/manual/en/function.iconv.php
[mb_detect_encoding()]: https://www.php.net/manual/en/function.mb-detect-encoding.php
[json_encode()]: https://www.php.net/manual/en/function.json-encode.php
[Normalizer::normalize()]: https://www.php.net/manual/en/normalizer.normalize.php
