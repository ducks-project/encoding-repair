# IconvTranscoder

(PHP 7 >= 7.4.0, PHP 8)

## Introduction

IconvTranscoder provides charset conversion using the iconv extension.
It offers good performance and supports transliteration.

**Priority:** 50 (medium)
**Requires:** ext-iconv

## Class Synopsis

```php
final class IconvTranscoder implements TranscoderInterface {
    /* Methods */
    public transcode(string $data, string $to, string $from, array $options): ?string
    public getPriority(): int
    public isAvailable(): bool
}
```

## Features

- Good performance
- Supports transliteration (//TRANSLIT)
- Supports ignoring invalid sequences (//IGNORE)
- Widely available on most systems
- Second in the transcoder chain (priority 50)

## Methods

### transcode

```php
public transcode(string $data, string $to, string $from, array $options): ?string
```

Transcode data using iconv with optional flags.

**Options:**

- `translit` (bool, default: true) - Enable //TRANSLIT
- `ignore` (bool, default: true) - Enable //IGNORE

**Returns:** Transcoded string or `null` if iconv is not available

### getPriority

```php
public getPriority(): int
```

**Returns:** `50` (medium priority)

### isAvailable

```php
public isAvailable(): bool
```

**Returns:** `true` if `iconv()` function exists, `false` otherwise

## Example

```php
<?php

use Ducks\Component\EncodingRepair\Transcoder\IconvTranscoder;

$transcoder = new IconvTranscoder();

if ($transcoder->isAvailable()) {
    $result = $transcoder->transcode('Café', 'UTF-8', 'ISO-8859-1', [
        'translit' => true,
        'ignore' => true
    ]);
    echo $result; // Café
}
```

## Transliteration

```php
$transcoder = new IconvTranscoder();

// Convert with transliteration
$result = $transcoder->transcode('Café', 'ASCII', 'UTF-8', [
    'translit' => true
]);
echo $result; // Cafe (é → e)
```

## Installation

```bash
# Ubuntu/Debian
sudo apt-get install php-iconv

# Usually included by default on most systems
```

## See Also

- [TranscoderInterface](./TranscoderInterface.md)
- [UConverterTranscoder](./UConverterTranscoder.md)
- [MbStringTranscoder](./MbStringTranscoder.md)
- [CharsetHelper::registerTranscoder](../CharsetHelper.registerTranscoder.md)
