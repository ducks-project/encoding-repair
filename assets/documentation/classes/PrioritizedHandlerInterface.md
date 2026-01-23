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

Ensures all handlers in Chain of Responsibility pattern implement priority-based ordering.

## Implementations

- [`TranscoderInterface`](TranscoderInterface.md) - Extends this interface
- [`DetectorInterface`](DetectorInterface.md) - Extends this interface
- [`TypeInterpreterInterface`](TypeInterpreterInterface.md) - Extends this interface

## Priority Conventions

- **100+**: High priority (executed first)
- **50-99**: Medium priority
- **1-49**: Low priority (fallback)

## Example

```php
use Ducks\Component\EncodingRepair\PrioritizedHandlerInterface;

class MyHandler implements PrioritizedHandlerInterface
{
    public function getPriority(): int
    {
        return 75; // Medium priority
    }
}
```

## Related

- [`ChainOfResponsibilityTrait`](ChainOfResponsibilityTrait.md)
- [`TranscoderInterface`](TranscoderInterface.md)
- [`DetectorInterface`](DetectorInterface.md)
- [`TypeInterpreterInterface`](TypeInterpreterInterface.md)
