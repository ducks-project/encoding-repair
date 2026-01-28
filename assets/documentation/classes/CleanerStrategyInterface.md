# CleanerStrategyInterface

Contract for cleaner execution strategies.

## Namespace

`Ducks\Component\EncodingRepair\Cleaner`

## Description

Defines the contract for strategies that control how cleaners are executed in the CleanerChain. Different strategies provide different execution patterns (Pipeline, First Match, Tagged).

## Methods

### execute()

Execute cleaners according to strategy.

```php
public function execute(
    iterable $cleaners,
    string $data,
    string $encoding,
    array $options
): ?string
```

**Parameters:**
- `$cleaners` (iterable<CleanerInterface>) - Available cleaners
- `$data` (string) - String to clean
- `$encoding` (string) - Target encoding
- `$options` (array<string, mixed>) - Cleaning options

**Returns:** `?string` - Cleaned string or null if no cleaner succeeded

## Implementations

- [`PipelineStrategy`](PipelineStrategy.md) - Applies all cleaners successively (middleware pattern)
- [`FirstMatchStrategy`](FirstMatchStrategy.md) - Stops at first successful cleaner (Chain of Responsibility)
- [`TaggedStrategy`](TaggedStrategy.md) - Applies only cleaners with matching tags

## Example

```php
use Ducks\Component\EncodingRepair\Cleaner\CleanerChain;
use Ducks\Component\EncodingRepair\Cleaner\PipelineStrategy;

$chain = new CleanerChain(new PipelineStrategy());
```

## See Also

- [CleanerChain](CleanerChain.md)
- [CleanerInterface](CleanerInterface.md)
