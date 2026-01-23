# PrioritizedHandlerInterface

Base interface for handlers with priority support in Chain of Responsibility pattern.

## Namespace

`Ducks\Component\EncodingRepair`

## Synopsis

```php
interface PrioritizedHandlerInterface
{
    public function getPriority(): int;
}
```

## Methods

### getPriority()

Get handler priority for Chain of Responsibility ordering.

```php
public function getPriority(): int
```

**Returns:** `int` - Priority value (higher = executed first)

## Purpose

Ensures all handlers in Chain of Responsibility pattern implement priority-based ordering. This interface is extended by:

- [TranscoderInterface](TranscoderInterface.md)
- [DetectorInterface](DetectorInterface.md)
- [TypeInterpreterInterface](TypeInterpreterInterface.md)

## Priority Conventions

| Range | Level | Usage |
|-------|-------|-------|
| 100+ | High | Primary handlers (UConverter, MbString, String) |
| 50-99 | Medium | Secondary handlers (Iconv, FileInfo, Array) |
| 1-49 | Low | Fallback handlers (MbString fallback, Object) |

## Example

```php
use Ducks\Component\EncodingRepair\PrioritizedHandlerInterface;
use Ducks\Component\EncodingRepair\Transcoder\TranscoderInterface;

class CustomTranscoder implements TranscoderInterface
{
    public function getPriority(): int
    {
        return 75; // Between iconv (50) and UConverter (100)
    }

    public function transcode(string $data, string $to, string $from, array $options): ?string
    {
        // Custom transcoding logic
        return $result;
    }

    public function isAvailable(): bool
    {
        return true;
    }
}
```

## See Also

- [ChainOfResponsibilityTrait](ChainOfResponsibilityTrait.md)
- [TranscoderInterface](TranscoderInterface.md)
- [DetectorInterface](DetectorInterface.md)
- [TypeInterpreterInterface](TypeInterpreterInterface.md)
