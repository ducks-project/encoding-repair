# TranscoderChain

Chain of Responsibility coordinator for transcoder strategies with priority-based execution.

## Namespace

```php
Ducks\Component\EncodingRepair\Transcoder\TranscoderChain
```

## Description

`TranscoderChain` implements the Chain of Responsibility pattern for managing multiple transcoding strategies. It coordinates the execution of registered [TranscoderInterface](TranscoderInterface.md) implementations, trying each transcoder in priority order until one successfully converts the data.

The chain automatically handles fallback logic: if a transcoder returns null (indicating it cannot handle the conversion), the next transcoder in the priority queue is tried.

## Class Declaration

```php
final class TranscoderChain
{
    public function register(TranscoderInterface $transcoder, ?int $priority = null): void;
    public function unregister(TranscoderInterface $transcoder): void;
    public function transcode(string $data, string $to, string $from, array $options): ?string;
}
```

## Features

- **Priority-based execution**: Higher priority transcoders execute first
- **Automatic fallback**: If one transcoder fails, the next is tried automatically
- **Dynamic registration**: Add or remove transcoders at runtime
- **Availability checking**: Skips unavailable transcoders (e.g., missing extensions)
- **Type-safe**: Full strict typing with comprehensive type declarations
- **Uses [ChainOfResponsibilityTrait](ChainOfResponsibilityTrait.md)**: Shared priority queue management

## Default Priority Order

When used by [CharsetHelper](CharsetHelper.md) or [CharsetProcessor](CharsetProcessor.md):

1. **UConverterTranscoder** (priority: 100) - Fastest, requires ext-intl
2. **IconvTranscoder** (priority: 50) - Good performance, supports transliteration
3. **MbStringTranscoder** (priority: 10) - Universal fallback, always available

## Methods

### register()

Register a transcoder with optional priority override.

```php
public function register(TranscoderInterface $transcoder, ?int $priority = null): void
```

**Parameters:**

- `$transcoder` (TranscoderInterface): Transcoder instance to register
- `$priority` (int|null): Priority override (null = use transcoder's getPriority())

**Notes:**

- Higher priority values execute first
- Multiple transcoders can have the same priority
- Queue is rebuilt on next transcode() call

**Example:**

```php
$chain = new TranscoderChain();
$chain->register(new UConverterTranscoder());  // Uses default priority: 100
$chain->register(new IconvTranscoder(), 150);  // Override priority
```

### unregister()

Unregister a transcoder from the chain.

```php
public function unregister(TranscoderInterface $transcoder): void
```

**Parameters:**

- `$transcoder` (TranscoderInterface): Transcoder instance to remove

**Notes:**

- Removes transcoder from registered handlers
- Queue is invalidated and rebuilt on next transcode() call
- Uses strict comparison (===) to match instance

**Example:**

```php
$iconv = new IconvTranscoder();
$chain->register($iconv);
$chain->unregister($iconv);
```

### transcode()

Execute transcoding using chain of responsibility.

```php
public function transcode(
    string $data,
    string $to,
    string $from,
    array $options
): ?string
```

**Parameters:**

- `$data` (string): Data to transcode
- `$to` (string): Target encoding (e.g., 'UTF-8')
- `$from` (string): Source encoding (e.g., 'ISO-8859-1')
- `$options` (array): Conversion options passed to transcoders

**Returns:** Transcoded string on success, or null if all transcoders failed

**Options:**

- `translit` (bool): Enable transliteration (iconv)
- `ignore` (bool): Ignore invalid sequences (iconv)
- `normalize` (bool): Apply Unicode normalization

**Example:**

```php
$chain = new TranscoderChain();
$chain->register(new IconvTranscoder());
$chain->register(new MbStringTranscoder());

$utf8 = $chain->transcode(
    $latinData,
    'UTF-8',
    'ISO-8859-1',
    ['translit' => true]
);

if (null === $utf8) {
    echo "All transcoders failed";
}
```

## Execution Flow

```text
transcode() called
    ↓
Rebuild priority queue
    ↓
For each transcoder (highest priority first):
    ↓
    Check isAvailable()
        ↓ (false) → Skip to next
        ↓ (true)
    Call transcoder->transcode()
        ↓ (null) → Try next
        ↓ (string) → Return immediately
    ↓
Return null (all failed)
```

## Usage Examples

### Basic Usage

```php
use Ducks\Component\EncodingRepair\Transcoder\TranscoderChain;
use Ducks\Component\EncodingRepair\Transcoder\UConverterTranscoder;
use Ducks\Component\EncodingRepair\Transcoder\IconvTranscoder;
use Ducks\Component\EncodingRepair\Transcoder\MbStringTranscoder;

$chain = new TranscoderChain();
$chain->register(new UConverterTranscoder());
$chain->register(new IconvTranscoder());
$chain->register(new MbStringTranscoder());

$utf8 = $chain->transcode($data, 'UTF-8', 'ISO-8859-1', []);
```

### Custom Transcoder

```php
use Ducks\Component\EncodingRepair\Transcoder\TranscoderInterface;

class MyCustomTranscoder implements TranscoderInterface
{
    public function transcode(string $data, string $to, string $from, ?array $options): ?string
    {
        if ($from === 'MY-ENCODING') {
            return myCustomConversion($data, $to);
        }
        return null;
    }
    
    public function getPriority(): int
    {
        return 75;
    }
    
    public function isAvailable(): bool
    {
        return extension_loaded('my_extension');
    }
}

$chain->register(new MyCustomTranscoder());
```

### Priority Override

```php
// Override default priority
$chain->register(new IconvTranscoder(), 150);
// Now IconvTranscoder executes before UConverterTranscoder (100)
```

## Performance

- **Queue Rebuild**: O(n log n) where n = number of transcoders
- **Transcoding**: O(n) worst case, O(1) best case
- **Memory**: Minimal overhead with SplPriorityQueue

**Tips:**

- Register transcoders in order of expected success rate
- Use priority to favor faster transcoders
- Remove unused transcoders to reduce overhead

## Thread Safety

TranscoderChain is **not thread-safe**. Each thread should have its own instance.

## See Also

- [TranscoderInterface](TranscoderInterface.md) - Transcoder contract
- [ChainOfResponsibilityTrait](ChainOfResponsibilityTrait.md) - Shared chain logic
- [UConverterTranscoder](UConverterTranscoder.md) - UConverter implementation
- [IconvTranscoder](IconvTranscoder.md) - Iconv implementation
- [MbStringTranscoder](MbStringTranscoder.md) - MbString implementation
- [CallableTranscoder](CallableTranscoder.md) - Callable wrapper
- [CharsetProcessor](CharsetProcessor.md) - Service using TranscoderChain
