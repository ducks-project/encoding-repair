# [The TranscoderChain class](#the-transcoderchain-class)

(PHP 7 >= 7.4.0, PHP 8)

## [Introduction](#introduction)

TranscoderChain implements the Chain of Responsibility pattern for managing multiple
transcoding strategies with priority-based execution order.
It coordinates the execution of registered [TranscoderInterface](TranscoderInterface.md)
implementations, trying each transcoder in priority order until one successfully converts the data.

The chain automatically handles fallback logic: if a transcoder returns null (indicating it cannot handle the conversion),
the next transcoder in the priority queue is tried. This provides robust encoding conversion with multiple fallback strategies.

**Architecture**: Uses [ChainOfResponsibilityTrait](ChainOfResponsibilityTrait.md)
 for priority queue management and handler registration.

## [Class synopsis](#class-synopsis)

```php
final class TranscoderChain {
    /* Methods */
    public register(TranscoderInterface $transcoder, ?int $priority = null): void

    public unregister(TranscoderInterface $transcoder): void

    public transcode(
        string $data,
        string $to,
        string $from,
        array $options
    ): ?string
}
```

## [Features](#features)

- **Priority-Based Execution**: Transcoders execute in priority order (highest first)
- **Automatic Fallback**: If one transcoder fails, the next is tried automatically
- **Dynamic Registration**: Add or remove transcoders at runtime
- **Availability Checking**: Skips unavailable transcoders (e.g., missing extensions)
- **Null Propagation**: Returns null only if all transcoders fail
- **Type-Safe**: Full strict typing with comprehensive type declarations

## [Default Priority Order](#default-priority-order)

When used by [CharsetHelper](CharsetHelper.md) or [CharsetProcessor](CharsetProcessor.md),
transcoders are registered with these priorities:

1. **UConverterTranscoder** (priority: 100) - Fastest, requires ext-intl
2. **IconvTranscoder** (priority: 50) - Good performance, supports transliteration
3. **MbStringTranscoder** (priority: 10) - Universal fallback, always available

## [Examples](#examples)

### Example #1 Basic usage with default transcoders

```php
<?php

use Ducks\Component\EncodingRepair\Transcoder\TranscoderChain;
use Ducks\Component\EncodingRepair\Transcoder\UConverterTranscoder;
use Ducks\Component\EncodingRepair\Transcoder\IconvTranscoder;
use Ducks\Component\EncodingRepair\Transcoder\MbStringTranscoder;

$chain = new TranscoderChain();

// Register transcoders (higher priority executes first)
$chain->register(new UConverterTranscoder());  // Priority: 100
$chain->register(new IconvTranscoder());       // Priority: 50
$chain->register(new MbStringTranscoder());    // Priority: 10

// Convert data
$utf8 = $chain->transcode(
    $latinData,
    'UTF-8',
    'ISO-8859-1',
    ['translit' => true]
);

echo $utf8; // Converted string or null if all failed
```

### Example #2 Custom priority override

```php
<?php

use Ducks\Component\EncodingRepair\Transcoder\TranscoderChain;
use Ducks\Component\EncodingRepair\Transcoder\IconvTranscoder;

$chain = new TranscoderChain();

// Override default priority (50) with custom priority
$chain->register(new IconvTranscoder(), 150);

// Now IconvTranscoder will execute before UConverterTranscoder (100)
```

### Example #3 Custom transcoder registration

```php
<?php

use Ducks\Component\EncodingRepair\Transcoder\TranscoderChain;
use Ducks\Component\EncodingRepair\Transcoder\TranscoderInterface;

class MyCustomTranscoder implements TranscoderInterface
{
    public function transcode(string $data, string $to, string $from, ?array $options): ?string
    {
        if ($from === 'MY-ENCODING') {
            return myCustomConversion($data, $to);
        }
        return null; // Pass to next transcoder
    }

    public function getPriority(): int
    {
        return 75; // Between iconv (50) and UConverter (100)
    }

    public function isAvailable(): bool
    {
        return extension_loaded('my_extension');
    }
}

$chain = new TranscoderChain();
$chain->register(new MyCustomTranscoder());

// Will use default priority from getPriority() = 75
```

### Example #4 Unregistering transcoders

```php
<?php

use Ducks\Component\EncodingRepair\Transcoder\TranscoderChain;
use Ducks\Component\EncodingRepair\Transcoder\IconvTranscoder;

$chain = new TranscoderChain();
$iconv = new IconvTranscoder();

$chain->register($iconv);

// Later, remove it from the chain
$chain->unregister($iconv);
```

### Example #5 Handling conversion failure

```php
<?php

use Ducks\Component\EncodingRepair\Transcoder\TranscoderChain;

$chain = new TranscoderChain();
// ... register transcoders

$result = $chain->transcode($data, 'UTF-8', 'UNKNOWN-ENCODING', []);

if (null === $result) {
    // All transcoders failed or returned null
    echo "Conversion failed: no transcoder could handle this encoding";
} else {
    echo "Conversion successful: {$result}";
}
```

## [Chain Execution Flow](#chain-execution-flow)

```text
transcode() called
    ↓
Rebuild priority queue
    ↓
For each transcoder (highest priority first):
    ↓
    Check isAvailable()
        ↓ (false) → Skip to next transcoder
        ↓ (true)
    Call transcoder->transcode()
        ↓ (null) → Try next transcoder
        ↓ (string) → Return result immediately
    ↓
All transcoders tried
    ↓
Return null (all failed)
```

## [Methods](#methods)

### register

Register a transcoder with optional priority override.

```php
public function register(TranscoderInterface $transcoder, ?int $priority = null): void
```

**Parameters:**

- **transcoder** (TranscoderInterface): Transcoder instance to register
- **priority** (int|null): Priority override (null = use transcoder's getPriority())

**Return Values:**

No value is returned.

**Notes:**

- Higher priority values execute first
- If priority is null, uses the transcoder's getPriority() method
- Multiple transcoders can have the same priority
- Transcoders are stored and queue is rebuilt on next transcode() call

### unregister

Unregister a transcoder from the chain.

```php
public function unregister(TranscoderInterface $transcoder): void
```

**Parameters:**

- **transcoder** (TranscoderInterface): Transcoder instance to remove

**Return Values:**

No value is returned.

**Notes:**

- Removes the transcoder from registered handlers
- Queue is invalidated and will be rebuilt on next transcode() call
- Uses strict comparison (===) to match transcoder instance

### transcode

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

- **data** (string): Data to transcode
- **to** (string): Target encoding (e.g., 'UTF-8')
- **from** (string): Source encoding (e.g., 'ISO-8859-1')
- **options** (array<string, mixed>): Conversion options passed to transcoders

**Return Values:**

Returns the transcoded string on success, or null if all transcoders failed.

**Notes:**

- Rebuilds priority queue before execution
- Tries each transcoder in priority order (highest first)
- Skips transcoders where isAvailable() returns false
- Returns immediately on first successful conversion
- Returns null only if all transcoders return null or are unavailable

**Options:**

Common options passed to transcoders:

- **translit** (bool): Enable transliteration (iconv)
- **ignore** (bool): Ignore invalid sequences (iconv)
- **normalize** (bool): Apply Unicode normalization

## [Performance](#performance)

- **Queue Rebuild**: O(n log n) where n is number of registered transcoders
- **Transcoding**: O(n) worst case (tries all transcoders), O(1) best case (first succeeds)
- **Memory**: Minimal overhead, uses SplPriorityQueue for efficient priority management

**Performance tips:**

- Register transcoders in order of expected success rate
- Use priority to favor faster transcoders (UConverter > iconv > mbstring)
- Remove unused transcoders to reduce iteration overhead

## [Thread Safety](#thread-safety)

TranscoderChain is **not thread-safe**. Each thread should have its own instance or use external synchronization.

## [See Also](#see-also)

- [TranscoderInterface](TranscoderInterface.md) — Interface for transcoder implementations
- [ChainOfResponsibilityTrait](ChainOfResponsibilityTrait.md) — Shared chain management logic
- [UConverterTranscoder](UConverterTranscoder.md) — UConverter-based transcoder
- [IconvTranscoder](IconvTranscoder.md) — Iconv-based transcoder
- [MbStringTranscoder](MbStringTranscoder.md) — MbString-based transcoder
- [CallableTranscoder](CallableTranscoder.md) — Callable wrapper transcoder
- [CharsetProcessor](CharsetProcessor.md) — Service using TranscoderChain
- [SplPriorityQueue] — PHP priority queue implementation

[SplPriorityQueue]: https://www.php.net/manual/en/class.splpriorityqueue.php
