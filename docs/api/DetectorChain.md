# DetectorChain

Chain of Responsibility coordinator for detector strategies with priority-based execution.

## Namespace

```php
Ducks\Component\EncodingRepair\Detector\DetectorChain
```

## Description

`DetectorChain` implements the Chain of Responsibility pattern for managing multiple encoding detection strategies. It coordinates the execution of registered [DetectorInterface](DetectorInterface.md) implementations, trying each detector in priority order until one successfully identifies the encoding.

The chain automatically handles fallback logic: if a detector returns null (indicating it cannot determine the encoding), the next detector in the priority queue is tried.

## Class Declaration

```php
final class DetectorChain
{
    public function register(DetectorInterface $detector, ?int $priority = null): void;
    public function unregister(DetectorInterface $detector): void;
    public function detect(string $string, array $options): ?string;
}
```

## Features

- **Priority-based execution**: Higher priority detectors execute first
- **Automatic fallback**: If one detector fails, the next is tried automatically
- **Dynamic registration**: Add or remove detectors at runtime
- **Availability checking**: Skips unavailable detectors (e.g., missing extensions)
- **Type-safe**: Full strict typing with comprehensive type declarations
- **Uses [ChainOfResponsibilityTrait](ChainOfResponsibilityTrait.md)**: Shared priority queue management

## Default Priority Order

When used by [CharsetHelper](CharsetHelper.md) or [CharsetProcessor](CharsetProcessor.md):

1. **MbStringDetector** (priority: 100) - Fast and reliable, uses mb_detect_encoding
2. **FileInfoDetector** (priority: 50) - Fallback method, uses finfo class

## Methods

### register()

Register a detector with optional priority override.

```php
public function register(DetectorInterface $detector, ?int $priority = null): void
```

**Parameters:**

- `$detector` (DetectorInterface): Detector instance to register
- `$priority` (int|null): Priority override (null = use detector's getPriority())

**Notes:**

- Higher priority values execute first
- Multiple detectors can have the same priority
- Queue is rebuilt on next detect() call

**Example:**

```php
$chain = new DetectorChain();
$chain->register(new MbStringDetector());  // Uses default priority: 100
$chain->register(new FileInfoDetector(), 150);  // Override priority
```

### unregister()

Unregister a detector from the chain.

```php
public function unregister(DetectorInterface $detector): void
```

**Parameters:**

- `$detector` (DetectorInterface): Detector instance to remove

**Notes:**

- Removes detector from registered handlers
- Queue is invalidated and rebuilt on next detect() call
- Uses strict comparison (===) to match instance

**Example:**

```php
$finfo = new FileInfoDetector();
$chain->register($finfo);
$chain->unregister($finfo);
```

### detect()

Execute detection using chain of responsibility.

```php
public function detect(string $string, array $options): ?string
```

**Parameters:**

- `$string` (string): String to analyze for encoding detection
- `$options` (array): Detection options passed to detectors

**Returns:** Detected encoding name (e.g., 'UTF-8', 'ISO-8859-1') on success, or null if all detectors failed

**Options:**

- `encodings` (array): List of encoding candidates (e.g., ['UTF-8', 'ISO-8859-1'])
- `strict` (bool): Use strict detection mode (MbStringDetector)
- `finfo_flags` (int): Flags for FileInfo detection
- `finfo_magic` (string): Custom magic database path

**Example:**

```php
$chain = new DetectorChain();
$chain->register(new MbStringDetector());
$chain->register(new FileInfoDetector());

$encoding = $chain->detect($unknownData, [
    'encodings' => ['UTF-8', 'ISO-8859-1', 'Windows-1252']
]);

if (null === $encoding) {
    echo "Detection failed, using fallback";
    $encoding = 'ISO-8859-1';
}
```

## Execution Flow

```text
detect() called
    ↓
Rebuild priority queue
    ↓
For each detector (highest priority first):
    ↓
    Check isAvailable()
        ↓ (false) → Skip to next
        ↓ (true)
    Call detector->detect()
        ↓ (null) → Try next
        ↓ (string) → Return immediately
    ↓
Return null (all failed)
```

## Usage Examples

### Basic Usage

```php
use Ducks\Component\EncodingRepair\Detector\DetectorChain;
use Ducks\Component\EncodingRepair\Detector\MbStringDetector;
use Ducks\Component\EncodingRepair\Detector\FileInfoDetector;

$chain = new DetectorChain();
$chain->register(new MbStringDetector());
$chain->register(new FileInfoDetector());

$encoding = $chain->detect('Café', ['encodings' => ['UTF-8', 'ISO-8859-1']]);
echo $encoding; // "UTF-8"
```

### Custom Detector

```php
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
        
        return null;
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

$chain->register(new BomDetector());
```

### Priority Override

```php
// Override default priority
$chain->register(new FileInfoDetector(), 150);
// Now FileInfoDetector executes before MbStringDetector (100)
```

### With Custom Encoding List

```php
$encoding = $chain->detect($japaneseText, [
    'encodings' => ['UTF-8', 'Shift_JIS', 'EUC-JP', 'ISO-2022-JP']
]);
```

## Detection Strategies

### MbStringDetector (Priority: 100)

Uses `mb_detect_encoding()` with configurable encoding list.

**Pros:**
- Fast and efficient
- Reliable for common encodings
- Supports strict mode

**Cons:**
- Requires ext-mbstring
- May return false positives for similar encodings

### FileInfoDetector (Priority: 50)

Uses `finfo` class to detect MIME encoding.

**Pros:**
- Works without mbstring
- Uses system magic database
- Good for file content

**Cons:**
- Requires ext-fileinfo
- Less accurate for short strings
- Limited encoding support

## Performance

- **Queue Rebuild**: O(n log n) where n = number of detectors
- **Detection**: O(n) worst case, O(1) best case
- **Memory**: Minimal overhead with SplPriorityQueue

**Tips:**

- Register detectors in order of expected success rate
- Provide specific encoding lists to reduce detection time
- Remove unused detectors to reduce overhead
- Cache detection results for repeated use

## Best Practices

1. **Always provide encoding candidates**: Improves accuracy and performance
2. **Use BOM detection first**: Most reliable when available
3. **Fallback to default**: Always have a fallback encoding if detection fails
4. **Cache results**: Detection can be expensive, cache for repeated use
5. **Validate results**: Verify detected encoding with `mb_check_encoding()`

## Thread Safety

DetectorChain is **not thread-safe**. Each thread should have its own instance.

## See Also

- [DetectorInterface](DetectorInterface.md) - Detector contract
- [ChainOfResponsibilityTrait](ChainOfResponsibilityTrait.md) - Shared chain logic
- [MbStringDetector](MbStringDetector.md) - MbString implementation
- [FileInfoDetector](FileInfoDetector.md) - FileInfo implementation
- [CallableDetector](CallableDetector.md) - Callable wrapper
- [CharsetProcessor](CharsetProcessor.md) - Service using DetectorChain
