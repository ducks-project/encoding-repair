# DetectorChain

Chain of Responsibility for detectors with priority management.
Uses [ChainOfResponsibilityTrait](./ChainOfResponsibilityTrait.md) for queue management.

## Synopsis

```php
namespace Ducks\Component\EncodingRepair\Detector;

final class DetectorChain
{
    use ChainOfResponsibilityTrait;

    public function register(DetectorInterface $detector, ?int $priority = null): void;
    public function detect(string $string, array $options): ?string;
}
```

## Description

Manages a priority queue of detectors using `SplPriorityQueue` via [ChainOfResponsibilityTrait](./ChainOfResponsibilityTrait.md). Executes detectors in priority order until one succeeds.

**Default Detectors:**
1. MbStringDetector (priority: 100)
2. FileInfoDetector (priority: 50)

## Methods

### register()

Register a detector with optional priority override.

```php
public function register(DetectorInterface $detector, ?int $priority = null): void
```

**Parameters:**
- `$detector` - Detector instance
- `$priority` - Priority override (null = use detector's default)

**Example:**

```php
use Ducks\Component\EncodingRepair\Detector\DetectorChain;
use Ducks\Component\EncodingRepair\Detector\MbStringDetector;

$chain = new DetectorChain();
$chain->register(new MbStringDetector());
$chain->register(new MbStringDetector(), 200); // Override priority
```

### detect()

Execute detection using chain of responsibility.

```php
public function detect(string $string, array $options): ?string
```

**Parameters:**
- `$string` - String to analyze
- `$options` - Detection options

**Returns:** Detected encoding or null if all detectors fail

**Example:**

```php
$chain = new DetectorChain();
$chain->register(new MbStringDetector());
$chain->register(new FileInfoDetector());

$encoding = $chain->detect('Café', ['encodings' => ['UTF-8', 'ISO-8859-1']]);
echo $encoding; // "UTF-8"
```

## Priority System

Higher priority values execute first:
- **100+**: Custom high-priority detectors
- **100**: MbStringDetector (default)
- **50**: FileInfoDetector (fallback)
- **0-49**: Custom low-priority detectors

## See Also

- [DetectorInterface](DetectorInterface.md)
- [ChainOfResponsibilityTrait](./ChainOfResponsibilityTrait.md)
- [MbStringDetector](MbStringDetector.md)
- [FileInfoDetector](FileInfoDetector.md)
- [CallableDetector](CallableDetector.md)
- [TranscoderChain](TranscoderChain.md)
