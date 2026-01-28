# [The CharsetHelper class](#the-charsethelper-class)

(PHP 7 >= 7.4.0, PHP 8)

## [Introduction](#introduction)

CharsetHelper is a static facade for charset processing that delegates to [CharsetProcessor](CharsetProcessor.md).
It implements the Chain of Responsibility pattern for extensible and robust character encoding conversion.
It provides automatic encoding detection, double-encoding repair capabilities, and safe JSON operations with UTF-8 compliance.

Unlike existing libraries, CharsetHelper offers multiple fallback strategies (UConverter → iconv → mbstring),
recursive conversion for arrays and objects, and the ability to repair corrupted double-encoded legacy data
commonly found in old databases.

**Architecture**: CharsetHelper is now a static facade that delegates all operations to [CharsetProcessor](CharsetProcessor.md).
For better testability and flexibility, consider using CharsetProcessor directly.

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
    public static is(string $string, string $encoding, array $options = []): bool

    public static toCharset(
        mixed $data,
        string $to = CharsetHelper::ENCODING_UTF8,
        string $from = CharsetHelper::ENCODING_ISO,
        array $options = []
    ): mixed

    public static toCharsetBatch(
        array $items,
        string $to = CharsetHelper::ENCODING_UTF8,
        string $from = CharsetHelper::ENCODING_ISO,
        array $options = []
    ): array

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

    public static detectBatch(iterable $items, array $options = []): string

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
        TranscoderInterface|callable $transcoder,
        ?int $priority = null
    ): void

    public static registerDetector(
        DetectorInterface|callable $detector,
        ?int $priority = null
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

### Example #1 Encoding validation

```php
<?php

use Ducks\Component\Component\EncodingRepair\CharsetHelper;

// Check if string is UTF-8
$data = 'Café résumé';
if (CharsetHelper::is($data, 'UTF-8')) {
    echo "String is valid UTF-8";
}

// Avoid unnecessary conversion
if (!CharsetHelper::is($data, 'UTF-8')) {
    $data = CharsetHelper::toUtf8($data, CharsetHelper::AUTO);
}

// Validate before database insert
$userInput = 'Gérard Müller';
if (CharsetHelper::is($userInput, 'UTF-8')) {
    $db->insert('users', ['name' => $userInput]);
}
```

### Example #2 Basic UTF-8 conversion

```php
<?php

use Ducks\Component\Component\EncodingRepair\CharsetHelper;

$latinString = "Café résumé";

// Convert to UTF-8
$utf8String = CharsetHelper::toUtf8($latinString, CharsetHelper::ENCODING_ISO);

echo $utf8String; // Café résumé (valid UTF-8)
```

### Example #3 Automatic encoding detection

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

### Example #4 Recursive array conversion

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

### Example #5 Repairing double-encoded strings

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

### Example #6 Safe JSON encoding

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

### Example #7 Registering custom transcoder

```php
<?php

use Ducks\Component\EncodingRepair\CharsetHelper;
use Ducks\Component\EncodingRepair\Transcoder\TranscoderInterface;

class MyCustomTranscoder implements TranscoderInterface
{
    public function transcode(string $data, string $to, string $from, array $options): ?string
    {
        if ($from === 'MY-CUSTOM-ENCODING') {
            return myCustomConversion($data, $to);
        }
        return null;
    }
    
    public function getPriority(): int
    {
        return 75;
    }
    
    public function isAvailable(): bool
    {
        return extension_loaded('my_extension');
    }
}

// Register with default priority
CharsetHelper::registerTranscoder(new MyCustomTranscoder());

// Register with custom priority
CharsetHelper::registerTranscoder(new MyCustomTranscoder(), 150);

// Legacy: Register a callable
CharsetHelper::registerTranscoder(
    function (string $data, string $to, string $from, ?array $options): ?string {
        if ($from === 'MY-CUSTOM-ENCODING') {
            return myCustomConversion($data, $to);
        }
        return null;
    },
    150  // Priority
);
```

### Example #8 Database migration

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

### Example #9 Conversion options

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

1. **MbStringDetector** (priority: 100): Fast and reliable using mb_detect_encoding
2. **FileInfoDetector** (priority: 50): Fallback using finfo class

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

- [CharsetHelper::is] — Check if string matches specified encoding
- [CharsetHelper::toCharset] — Convert data from one encoding to another
- [CharsetHelper::toCharsetBatch] — Batch convert array items with optimized detection
- [CharsetHelper::toUtf8] — Convert data to UTF-8
- [CharsetHelper::toIso] — Convert data to ISO-8859-1/Windows-1252
- [CharsetHelper::detect] — Detect charset encoding of a string
- [CharsetHelper::detectBatch] — Detect charset encoding from iterable items
- [CharsetHelper::repair] — Repair double-encoded strings
- [CharsetHelper::safeJsonEncode] — JSON encode with automatic charset repair
- [CharsetHelper::safeJsonDecode] — JSON decode with charset conversion
- [CharsetHelper::registerTranscoder] — Register custom transcoding strategy
- [CharsetHelper::registerDetector] — Register custom detection strategy
- [TranscoderInterface] — Interface for custom transcoders
- [UConverterTranscoder] — UConverter-based transcoder (ext-intl)
- [IconvTranscoder] — Iconv-based transcoder (ext-iconv)
- [MbStringTranscoder] — MbString-based transcoder (ext-mbstring)
- [CallableTranscoder] — Callable wrapper transcoder

## [See Also](#see-also)

- [CharsetProcessor](CharsetProcessor.md) — Service implementation
- [CharsetProcessorInterface](CharsetProcessorInterface.md) — Service contract
- [mb_convert_encoding()] — Convert character encoding
- [iconv()] — Convert string to requested character encoding
- [mb_detect_encoding()] — Detect character encoding
- [json_encode()] — Returns the JSON representation of a value
- [Normalizer::normalize()] — Normalizes the input provided

[CharsetHelper::is]: ./CharsetHelper.is.md#charsethelper__is
[CharsetHelper::toCharset]: ./CharsetHelper.toCharset.md#charsethelper__tocharset
[CharsetHelper::toCharsetBatch]: ./CharsetHelper.toCharsetBatch.md#charsethelper__tocharsetbatch
[CharsetHelper::toUtf8]: ./CharsetHelper.toUtf8.md#charsethelper__toutf8
[CharsetHelper::toIso]: ./CharsetHelper.toIso.md#charsethelper__toiso
[CharsetHelper::detect]: ./CharsetHelper.detect.md#charsethelper__detect
[CharsetHelper::detectBatch]: ./CharsetHelper.detectBatch.md#charsethelper__detectbatch
[CharsetHelper::repair]: ./CharsetHelper.repair.md#charsethelper__repair
[CharsetHelper::safeJsonEncode]: ./CharsetHelper.safeJsonEncode.md#charsethelper__safejsonencode
[CharsetHelper::safeJsonDecode]: ./CharsetHelper.safeJsonDecode.md#charsethelper__safejsondecode
[CharsetHelper::registerTranscoder]: ./CharsetHelper.registerTranscoder.md#charsethelper__registertranscoder
[CharsetHelper::registerDetector]: ./CharsetHelper.registerDetector.md#charsethelper__registerdetector
[TranscoderInterface]: ./TranscoderInterface.md
[UConverterTranscoder]: ./UConverterTranscoder.md
[IconvTranscoder]: ./IconvTranscoder.md
[MbStringTranscoder]: ./MbStringTranscoder.md
[CallableTranscoder]: ./CallableTranscoder.md
[mb_convert_encoding()]: https://www.php.net/manual/en/function.mb-convert-encoding.php
[iconv()]: https://www.php.net/manual/en/function.iconv.php
[mb_detect_encoding()]: https://www.php.net/manual/en/function.mb-detect-encoding.php
[json_encode()]: https://www.php.net/manual/en/function.json-encode.php
[Normalizer::normalize()]: https://www.php.net/manual/en/normalizer.normalize.php
