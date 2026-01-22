# DetectorInterface

(PHP 7 >= 7.4.0, PHP 8)

## Introduction

DetectorInterface defines the contract for implementing custom charset detectors in CharsetHelper.

## Interface Synopsis

```php
interface DetectorInterface {
    public detect(string $string, array $options): ?string
    public getPriority(): int
    public isAvailable(): bool
}
```

## Methods

- `detect()` - Detect charset encoding
- `getPriority()` - Get detector priority (higher = first)
- `isAvailable()` - Check if detector is available

## Example

```php
use Ducks\Component\EncodingRepair\Detector\DetectorInterface;

class CustomDetector implements DetectorInterface
{
    public function detect(string $string, array $options): ?string
    {
        // Custom detection logic
        return 'UTF-8';
    }
    
    public function getPriority(): int
    {
        return 75;
    }
    
    public function isAvailable(): bool
    {
        return true;
    }
}
```

## See Also

- [MbStringDetector](./MbStringDetector.md)
- [FileInfoDetector](./FileInfoDetector.md)
- [CallableDetector](./CallableDetector.md)
