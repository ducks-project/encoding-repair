# UConverterTranscoder

(PHP 7 >= 7.4.0, PHP 8)

## Introduction

UConverterTranscoder provides charset conversion using the UConverter class from the intl extension. It offers the best precision and performance among all transcoders.

**Priority:** 100 (highest)  
**Requires:** ext-intl

## Class Synopsis

```php
final class UConverterTranscoder implements TranscoderInterface {
    /* Methods */
    public transcode(string $data, string $to, string $from, array $options): ?string
    public getPriority(): int
    public isAvailable(): bool
}
```

## Features

- Best precision for charset conversion
- Supports many encodings
- ~30% faster than iconv
- Handles complex Unicode transformations
- First in the transcoder chain (priority 100)

## Methods

### transcode

```php
public transcode(string $data, string $to, string $from, array $options): ?string
```

Transcode data using UConverter.

**Returns:** Transcoded string or `null` if UConverter is not available

### getPriority

```php
public getPriority(): int
```

**Returns:** `100` (highest priority)

### isAvailable

```php
public isAvailable(): bool
```

**Returns:** `true` if `UConverter` class exists, `false` otherwise

## Example

```php
<?php

use Ducks\Component\EncodingRepair\Transcoder\UConverterTranscoder;

$transcoder = new UConverterTranscoder();

if ($transcoder->isAvailable()) {
    $result = $transcoder->transcode('Café', 'UTF-8', 'ISO-8859-1', []);
    echo $result; // Café
}
```

## Installation

```bash
# Ubuntu/Debian
sudo apt-get install php-intl

# macOS
brew install php@8.2  # Includes intl

# Windows
# Enable in php.ini:
extension=intl
```

## See Also

- [TranscoderInterface](./TranscoderInterface.md)
- [IconvTranscoder](./IconvTranscoder.md)
- [MbStringTranscoder](./MbStringTranscoder.md)
- [CharsetHelper::registerTranscoder](../CharsetHelper.registerTranscoder.md)
