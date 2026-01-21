# MbStringTranscoder

(PHP 7 >= 7.4.0, PHP 8)

## Introduction

MbStringTranscoder provides charset conversion using the mbstring extension. It serves as the universal fallback transcoder.

**Priority:** 10 (lowest)  
**Requires:** ext-mbstring (required)

## Class Synopsis

```php
final class MbStringTranscoder implements TranscoderInterface {
    /* Methods */
    public transcode(string $data, string $to, string $from, array $options): ?string
    public getPriority(): int
    public isAvailable(): bool
}
```

## Features

- Universal fallback (always available)
- Most permissive transcoder
- Handles many encodings
- Required extension for CharsetHelper
- Last in the transcoder chain (priority 10)

## Methods

### transcode

```php
public transcode(string $data, string $to, string $from, array $options): ?string
```

Transcode data using mb_convert_encoding.

**Returns:** Transcoded string or `null` if mbstring is not available

### getPriority

```php
public getPriority(): int
```

**Returns:** `10` (lowest priority)

### isAvailable

```php
public isAvailable(): bool
```

**Returns:** `true` if `mb_convert_encoding()` function exists, `false` otherwise

## Example

```php
<?php

use Ducks\Component\EncodingRepair\Transcoder\MbStringTranscoder;

$transcoder = new MbStringTranscoder();

if ($transcoder->isAvailable()) {
    $result = $transcoder->transcode('Café', 'UTF-8', 'ISO-8859-1', []);
    echo $result; // Café
}
```

## Why Lowest Priority?

MbStringTranscoder has the lowest priority because:

1. Less precise than UConverter
2. No transliteration support like iconv
3. More permissive (may accept invalid sequences)
4. Used as final fallback when other transcoders fail

## Installation

```bash
# Ubuntu/Debian
sudo apt-get install php-mbstring

# Required extension - must be installed
```

## See Also

- [TranscoderInterface](./TranscoderInterface.md)
- [UConverterTranscoder](./UConverterTranscoder.md)
- [IconvTranscoder](./IconvTranscoder.md)
- [CharsetHelper::registerTranscoder](../CharsetHelper.registerTranscoder.md)
