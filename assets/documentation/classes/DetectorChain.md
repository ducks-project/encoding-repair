# [The DetectorChain class](#the-detectorchain-class)

(PHP 7 >= 7.4.0, PHP 8)

## [Introduction](#introduction)

DetectorChain implements the Chain of Responsibility pattern for managing multiple encoding detection
strategies with priority-based execution order.
It coordinates the execution of registered [DetectorInterface](DetectorInterface.md) implementations,
trying each detector in priority order until one successfully identifies the encoding.

The chain automatically handles fallback logic: if a detector returns null (indicating it cannot determine the encoding),
the next detector in the priority queue is tried. This provides robust encoding detection with multiple fallback strategies.

**Architecture**: Uses [ChainOfResponsibilityTrait](ChainOfResponsibilityTrait.md)
for priority queue management and handler registration.

## [Class synopsis](#class-synopsis)

```php
final class DetectorChain {
    /* Methods */
    public register(DetectorInterface $detector, ?int $priority = null): void

    public unregister(DetectorInterface $detector): void

    public detect(string $string, array $options): ?string
}
```

## [Features](#features)

- **Priority-Based Execution**: Detectors execute in priority order (highest first)
- **Automatic Fallback**: If one detector fails, the next is tried automatically
- **Dynamic Registration**: Add or remove detectors at runtime
- **Availability Checking**: Skips unavailable detectors (e.g., missing extensions)
- **Null Propagation**: Returns null only if all detectors fail
- **Type-Safe**: Full strict typing with comprehensive type declarations

## [Default Priority Order](#default-priority-order)

When used by [CharsetHelper](CharsetHelper.md) or [CharsetProcessor](CharsetProcessor.md),
detectors are registered with these priorities:

1. **MbStringDetector** (priority: 100) - Fast and reliable, uses mb_detect_encoding
2. **FileInfoDetector** (priority: 50) - Fallback method, uses finfo class

## [Examples](#examples)

### Example #1 Basic usage with default detectors

```php
<?php

use Ducks\Component\EncodingRepair\Detector\DetectorChain;
use Ducks\Component\EncodingRepair\Detector\MbStringDetector;
use Ducks\Component\EncodingRepair\Detector\FileInfoDetector;

$chain = new DetectorChain();

// Register detectors (higher priority executes first)
$chain->register(new MbStringDetector());  // Priority: 100
$chain->register(new FileInfoDetector());  // Priority: 50

// Detect encoding
$encoding = $chain->detect($unknownData, [
    'encodings' => ['UTF-8', 'ISO-8859-1', 'Windows-1252']
]);

echo $encoding; // 'UTF-8', 'ISO-8859-1', etc., or null if detection failed
```

### Example #2 Custom priority override

```php
<?php

use Ducks\Component\EncodingRepair\Detector\DetectorChain;
use Ducks\Component\EncodingRepair\Detector\FileInfoDetector;

$chain = new DetectorChain();

// Override default priority (50) with custom priority
$chain->register(new FileInfoDetector(), 150);

// Now FileInfoDetector will execute before MbStringDetector (100)
```

### Example #3 Custom detector registration

```php
<?php

use Ducks\Component\EncodingRepair\Detector\DetectorChain;
use Ducks\Component\EncodingRepair\Detector\DetectorInterface;

class BomDetector implements DetectorInterface
{
    public function detect(string $string, ?array $options = null): ?string
    {
        // Check for UTF-8 BOM
        if (strlen($string) >= 3 && substr($string, 0, 3) === "\xEF\xBB\xBF") {
            return 'UTF-8';
        }

        // Check for UTF-16LE BOM
        if (strlen($string) >= 2 && ord($string[0]) === 0xFF && ord($string[1]) === 0xFE) {
            return 'UTF-16LE';
        }

        return null; // Pass to next detector
    }

    public function getPriority(): int
    {
        return 200; // Highest priority - BOM is most reliable
    }

    public function isAvailable(): bool
    {
        return true;
    }
}

$chain = new DetectorChain();
$chain->register(new BomDetector());

// Will use default priority from getPriority() = 200
```

### Example #4 Unregistering detectors

```php
<?php

use Ducks\Component\EncodingRepair\Detector\DetectorChain;
use Ducks\Component\EncodingRepair\Detector\FileInfoDetector;

$chain = new DetectorChain();
$finfo = new FileInfoDetector();

$chain->register($finfo);

// Later, remove it from the chain
$chain->unregister($finfo);
```

### Example #5 Handling detection failure

```php
<?php

use Ducks\Component\EncodingRepair\Detector\DetectorChain;

$chain = new DetectorChain();
// ... register detectors

$encoding = $chain->detect($data, ['encodings' => ['UTF-8', 'ISO-8859-1']]);

if (null === $encoding) {
    // All detectors failed
    echo "Could not detect encoding, using fallback";
    $encoding = 'ISO-8859-1'; // Default fallback
} else {
    echo "Detected encoding: {$encoding}";
}
```

### Example #6 Detection with custom encoding list

```php
<?php

use Ducks\Component\EncodingRepair\Detector\DetectorChain;

$chain = new DetectorChain();
// ... register detectors

// Detect with specific encoding candidates
$encoding = $chain->detect($japaneseText, [
    'encodings' => ['UTF-8', 'Shift_JIS', 'EUC-JP', 'ISO-2022-JP']
]);

echo "Detected: {$encoding}";
```

## [Chain Execution Flow](#chain-execution-flow)

```text
detect() called
    ↓
Rebuild priority queue
    ↓
For each detector (highest priority first):
    ↓
    Check isAvailable()
        ↓ (false) → Skip to next detector
        ↓ (true)
    Call detector->detect()
        ↓ (null) → Try next detector
        ↓ (string) → Return encoding immediately
    ↓
All detectors tried
    ↓
Return null (all failed)
```

## [Methods](#methods)

### register

Register a detector with optional priority override.

```php
public function register(DetectorInterface $detector, ?int $priority = null): void
```

**Parameters:**

- **detector** (DetectorInterface): Detector instance to register
- **priority** (int|null): Priority override (null = use detector's getPriority())

**Return Values:**

No value is returned.

**Notes:**

- Higher priority values execute first
- If priority is null, uses the detector's getPriority() method
- Multiple detectors can have the same priority
- Detectors are stored and queue is rebuilt on next detect() call

### unregister

Unregister a detector from the chain.

```php
public function unregister(DetectorInterface $detector): void
```

**Parameters:**

- **detector** (DetectorInterface): Detector instance to remove

**Return Values:**

No value is returned.

**Notes:**

- Removes the detector from registered handlers
- Queue is invalidated and will be rebuilt on next detect() call
- Uses strict comparison (===) to match detector instance

### detect

Execute detection using chain of responsibility.

```php
public function detect(string $string, array $options): ?string
```

**Parameters:**

- **string** (string): String to analyze for encoding detection
- **options** (array<string, mixed>): Detection options passed to detectors

**Return Values:**

Returns the detected encoding name (e.g., 'UTF-8', 'ISO-8859-1') on success, or null if all detectors failed.

**Notes:**

- Rebuilds priority queue before execution
- Tries each detector in priority order (highest first)
- Skips detectors where isAvailable() returns false
- Returns immediately on first successful detection
- Returns null only if all detectors return null or are unavailable

**Options:**

Common options passed to detectors:

- **encodings** (array): List of encoding candidates to check (e.g., ['UTF-8', 'ISO-8859-1'])
- **strict** (bool): Use strict detection mode (MbStringDetector)
- **finfo_flags** (int): Flags for FileInfo detection
- **finfo_magic** (string): Custom magic database path

## [Detection Strategies](#detection-strategies)

### MbStringDetector (Priority: 100)

Uses mb_detect_encoding() with configurable encoding list.

**Pros:**

- Fast and efficient
- Reliable for common encodings
- Supports strict mode

**Cons:**

- Requires ext-mbstring
- May return false positives for similar encodings

### FileInfoDetector (Priority: 50)

Uses finfo class to detect MIME encoding.

**Pros:**

- Works without mbstring
- Uses system magic database
- Good for file content

**Cons:**

- Requires ext-fileinfo
- Less accurate for short strings
- Limited encoding support

## [Performance](#performance)

- **Queue Rebuild**: O(n log n) where n is number of registered detectors
- **Detection**: O(n) worst case (tries all detectors), O(1) best case (first succeeds)
- **Memory**: Minimal overhead, uses SplPriorityQueue for efficient priority management

**Performance tips:**

- Register detectors in order of expected success rate
- Use priority to favor faster detectors (MbString > FileInfo)
- Provide specific encoding lists to reduce detection time
- Remove unused detectors to reduce iteration overhead

## [Best Practices](#best-practices)

1. **Always provide encoding candidates**: Improves accuracy and performance
2. **Use BOM detection first**: Most reliable when available
3. **Fallback to default**: Always have a fallback encoding if detection fails
4. **Cache results**: Detection can be expensive, cache for repeated use
5. **Validate results**: Verify detected encoding with mb_check_encoding()

## [Thread Safety](#thread-safety)

DetectorChain is **not thread-safe**. Each thread should have its own instance or use external synchronization.

## [See Also](#see-also)

- [DetectorInterface](DetectorInterface.md) — Interface for detector implementations
- [ChainOfResponsibilityTrait](ChainOfResponsibilityTrait.md) — Shared chain management logic
- [MbStringDetector](MbStringDetector.md) — MbString-based detector
- [FileInfoDetector](FileInfoDetector.md) — FileInfo-based detector
- [CallableDetector](CallableDetector.md) — Callable wrapper detector
- [CharsetProcessor](CharsetProcessor.md) — Service using DetectorChain
- [SplPriorityQueue] — PHP priority queue implementation
- [mb_detect_encoding()] — PHP encoding detection function

[SplPriorityQueue]: https://www.php.net/manual/en/class.splpriorityqueue.php
[mb_detect_encoding()]: https://www.php.net/manual/en/function.mb-detect-encoding.php
