# TranscoderInterface

(PHP 7 >= 7.4.0, PHP 8)

## Introduction

TranscoderInterface defines the contract for implementing custom charset
transcoders in CharsetHelper.
It enables extensibility through the Chain of Responsibility pattern
with priority-based execution.

## Interface Synopsis

```php
interface TranscoderInterface {
    /* Methods */
    public transcode(string $data, string $to, string $from, array $options): ?string
    public getPriority(): int
    public isAvailable(): bool
}
```

## Methods

### transcode

Transcode data from one encoding to another.

```php
public transcode(string $data, string $to, string $from, array $options): ?string
```

**Parameters:**

- `$data` - String data to transcode
- `$to` - Target encoding (e.g., 'UTF-8')
- `$from` - Source encoding (e.g., 'ISO-8859-1')
- `$options` - Conversion options array

**Returns:** Transcoded string or `null` if transcoder cannot handle the conversion

### getPriority

Get transcoder execution priority.

```php
public getPriority(): int
```

**Returns:** Priority value (higher = executed first)

**Default priorities:**

- UConverter: 100
- Iconv: 50
- MbString: 10

### isAvailable

Check if transcoder is available on current system.

```php
public isAvailable(): bool
```

**Returns:** `true` if transcoder can be used, `false` otherwise

## Examples

### Example #1 Basic Custom Transcoder

```php
<?php

use Ducks\Component\EncodingRepair\Transcoder\TranscoderInterface;
use Ducks\Component\EncodingRepair\CharsetHelper;

class Base64Transcoder implements TranscoderInterface
{
    public function transcode(string $data, string $to, string $from, array $options): ?string
    {
        if ('BASE64' !== $from) {
            return null;
        }

        $decoded = base64_decode($data, true);
        if (false === $decoded) {
            return null;
        }

        return mb_convert_encoding($decoded, $to, 'UTF-8');
    }

    public function getPriority(): int
    {
        return 60;
    }

    public function isAvailable(): bool
    {
        return function_exists('base64_decode');
    }
}

CharsetHelper::registerTranscoder(new Base64Transcoder());
```

### Example #2 Transcoder with Extension Check

```php
<?php

use Ducks\Component\EncodingRepair\Transcoder\TranscoderInterface;

class CustomTranscoder implements TranscoderInterface
{
    public function transcode(string $data, string $to, string $from, array $options): ?string
    {
        if (!$this->isAvailable()) {
            return null;
        }

        // Custom conversion logic
        return my_custom_convert($data, $to, $from);
    }

    public function getPriority(): int
    {
        return 80;
    }

    public function isAvailable(): bool
    {
        return extension_loaded('my_custom_extension');
    }
}
```

### Example #3 Priority Override

```php
<?php

use Ducks\Component\EncodingRepair\CharsetHelper;

$transcoder = new MyTranscoder(); // Default priority: 50

// Register with default priority
CharsetHelper::registerTranscoder($transcoder);

// Register with custom priority (highest)
CharsetHelper::registerTranscoder($transcoder, 200);
```

## See Also

- [CharsetHelper::registerTranscoder](../CharsetHelper.registerTranscoder.md)
- [UConverterTranscoder](./UConverterTranscoder.md)
- [IconvTranscoder](./IconvTranscoder.md)
- [MbStringTranscoder](./MbStringTranscoder.md)
- [CallableTranscoder](./CallableTranscoder.md)
