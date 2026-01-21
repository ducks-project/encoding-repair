# TranscoderInterface

## Overview

The `TranscoderInterface` defines the contract for implementing custom charset transcoders. It enables extensibility through the Chain of Responsibility pattern with priority-based execution.

## Interface

```php
namespace Ducks\Component\EncodingRepair\Transcoder;

interface TranscoderInterface
{
    public function transcode(string $data, string $to, string $from, array $options): ?string;
    public function getPriority(): int;
    public function isAvailable(): bool;
}
```

## Methods

### transcode()

Transcode data from one encoding to another.

**Parameters:**
- `$data` (string) - Data to transcode
- `$to` (string) - Target encoding
- `$from` (string) - Source encoding
- `$options` (array) - Conversion options

**Returns:** `string|null` - Transcoded string or null if cannot handle

### getPriority()

Get transcoder execution priority.

**Returns:** `int` - Priority value (higher = executed first)

**Default priorities:**
- UConverter: 100
- Iconv: 50
- MbString: 10

### isAvailable()

Check if transcoder is available on current system.

**Returns:** `bool` - True if available

## Example

```php
use Ducks\Component\EncodingRepair\Transcoder\TranscoderInterface;
use Ducks\Component\EncodingRepair\CharsetHelper;

class CustomTranscoder implements TranscoderInterface
{
    public function transcode(string $data, string $to, string $from, array $options): ?string
    {
        if ('CUSTOM' !== $from) {
            return null;
        }
        
        return $this->customConvert($data, $to);
    }
    
    public function getPriority(): int
    {
        return 75;
    }
    
    public function isAvailable(): bool
    {
        return extension_loaded('my_ext');
    }
    
    private function customConvert(string $data, string $to): string
    {
        // Custom logic
        return $data;
    }
}

CharsetHelper::registerTranscoder(new CustomTranscoder());
```

## Built-in Implementations

- [UConverterTranscoder](UConverterTranscoder.md) - Uses ext-intl (priority: 100)
- [IconvTranscoder](IconvTranscoder.md) - Uses ext-iconv (priority: 50)
- [MbStringTranscoder](MbStringTranscoder.md) - Uses ext-mbstring (priority: 10)
- [CallableTranscoder](CallableTranscoder.md) - Wraps legacy callables

## See Also

- [Advanced Usage](../guide/advanced-usage.md)
- [CharsetHelper](CharsetHelper.md)
