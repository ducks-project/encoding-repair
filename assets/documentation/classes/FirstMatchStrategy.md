# FirstMatchStrategy

Strategy that stops at first successful cleaner (Chain of Responsibility pattern).

## Namespace

`Ducks\Component\EncodingRepair\Cleaner`

## Description

Implements the Chain of Responsibility pattern by stopping execution at the first cleaner that successfully processes the data. This provides optimal performance when only one cleaning operation is needed.

## Usage

```php
use Ducks\Component\EncodingRepair\Cleaner\CleanerChain;
use Ducks\Component\EncodingRepair\Cleaner\FirstMatchStrategy;
use Ducks\Component\EncodingRepair\Cleaner\MbScrubCleaner;
use Ducks\Component\EncodingRepair\Cleaner\PregMatchCleaner;

// Create chain with first match strategy
$chain = new CleanerChain(new FirstMatchStrategy());
$chain->register(new MbScrubCleaner());
$chain->register(new PregMatchCleaner());

// Only MbScrubCleaner is executed (stops at first success)
$result = $chain->clean($data, 'UTF-8', []);
```

## Behavior

1. Iterates through cleaners in priority order
2. Returns immediately when a cleaner succeeds (returns non-null)
3. Continues to next cleaner if current one returns `null`
4. Skips unavailable cleaners
5. Returns `null` if no cleaner succeeds

## Use Cases

- **Performance optimization**: Only one cleaning operation needed
- **Fallback mechanism**: Try best cleaner first, fallback to alternatives
- **Simple cleaning**: Single issue to fix

## Performance

Fastest strategy as it stops at first success. Ideal for high-performance scenarios where only one cleaner is expected to match.

## Example: Performance Optimization

```php
$chain = new CleanerChain(new FirstMatchStrategy());
$chain->register(new MbScrubCleaner(), 100);  // Best quality
$chain->register(new PregMatchCleaner(), 50);  // Fastest
$chain->register(new IconvCleaner(), 10);      // Universal fallback

$result = $chain->clean($data, 'UTF-8', []);
// Stops at MbScrubCleaner if successful
```

## Comparison with PipelineStrategy

| Aspect | FirstMatchStrategy | PipelineStrategy |
|--------|-------------------|------------------|
| Execution | Stops at first success | Applies all cleaners |
| Performance | Fastest | Slower |
| Use case | Single issue | Multiple issues |
| Pattern | Chain of Responsibility | Middleware |

## See Also

- [CleanerStrategyInterface](CleanerStrategyInterface.md)
- [PipelineStrategy](PipelineStrategy.md)
- [TaggedStrategy](TaggedStrategy.md)
- [CleanerChain](CleanerChain.md)
