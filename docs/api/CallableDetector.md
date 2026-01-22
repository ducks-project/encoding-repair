# CallableDetector

Adapter for legacy callable detectors.

Uses [CallableAdapterTrait](CallableAdapterTrait.md) for common adapter functionality.

## Synopsis

```php
namespace Ducks\Component\EncodingRepair\Detector;

final class CallableDetector implements DetectorInterface
{
    use CallableAdapterTrait;

    public function __construct(callable $callable, int $priority);
    public function detect(string $string, array $options): ?string;
    public function getPriority(): int;  // From CallableAdapterTrait
    public function isAvailable(): bool;  // From CallableAdapterTrait
}
```

## Description

Wraps a callable to implement DetectorInterface, enabling legacy callables to work with the detector chain.

**Validation**: Ensures callable accepts at least 1 parameter and returns string|null

## Constructor

```php
public function __construct(callable $callable, int $priority)
```

**Parameters:**
- `$callable` - Callable with signature: `fn(string, array): (string|null)`
- `$priority` - Detector priority

**Throws:** InvalidArgumentException if callable signature is invalid

## Methods

### detect()

Executes the wrapped callable.

```php
public function detect(string $string, array $options): ?string
```

**Parameters:**
- `$string` - String to analyze
- `$options` - Detection options

**Returns:** Detected encoding or null

**Throws:** InvalidArgumentException if callable returns invalid type

**Example:**

```php
use Ducks\Component\EncodingRepair\Detector\CallableDetector;

$callable = function (string $string, array $options): ?string {
    if (str_starts_with($string, "\xFF\xFE")) {
        return 'UTF-16LE';
    }
    return null;
};

$detector = new CallableDetector($callable, 150);
$encoding = $detector->detect("\xFF\xFEtest", []);
echo $encoding; // "UTF-16LE"
```

### getPriority()

Returns detector priority.

```php
public function getPriority(): int
```

**Returns:** Priority specified in constructor

### isAvailable()

Checks if detector is available.

```php
public function isAvailable(): bool
```

**Returns:** Always true

## See Also

- [DetectorInterface](DetectorInterface.md)
- [CallableAdapterTrait](CallableAdapterTrait.md)
- [DetectorChain](DetectorChain.md)
- [CallableTranscoder](CallableTranscoder.md)
