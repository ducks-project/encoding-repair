# PipelineStrategy

Strategy that applies all cleaners successively (middleware pattern).

## Namespace

`Ducks\Component\EncodingRepair\Cleaner`

## Description

Implements the middleware pattern by applying all available cleaners in sequence. Each cleaner processes the output of the previous one, allowing multiple transformations to be chained together.

This is the **default strategy** used by CleanerChain.

## Usage

```php
use Ducks\Component\EncodingRepair\Cleaner\CleanerChain;
use Ducks\Component\EncodingRepair\Cleaner\PipelineStrategy;
use Ducks\Component\EncodingRepair\Cleaner\BomCleaner;
use Ducks\Component\EncodingRepair\Cleaner\HtmlEntityCleaner;

// Create chain with pipeline strategy
$chain = new CleanerChain(new PipelineStrategy());
$chain->register(new BomCleaner());
$chain->register(new HtmlEntityCleaner());

// Both cleaners are applied: BOM removed, then HTML entities decoded
$result = $chain->clean($data, 'UTF-8', []);
```

## Behavior

1. Iterates through all cleaners in priority order
2. Passes the output of each cleaner to the next one
3. Skips cleaners that return `null`
4. Skips unavailable cleaners
5. Returns the final result or `null` if no cleaner modified the data

## Use Cases

- **Multiple issues**: String has BOM + HTML entities + whitespace issues
- **Sequential processing**: Each cleaner handles a specific aspect
- **Comprehensive cleaning**: Apply all registered cleaners

## Performance

Slower than FirstMatchStrategy as it executes all cleaners, but necessary when multiple cleaning operations are required.

## Example: Complex Cleaning

```php
$chain = new CleanerChain(new PipelineStrategy());
$chain->register(new BomCleaner(), 150);
$chain->register(new HtmlEntityCleaner(), 100);
$chain->register(new WhitespaceCleaner(), 50);

$data = "\xEF\xBB\xBF" . 'Caf&eacute;   &amp;   Restaurant  ';
$result = $chain->clean($data, 'UTF-8', []);
// Result: 'Café & Restaurant' (all cleaners applied)
```

## See Also

- [CleanerStrategyInterface](CleanerStrategyInterface.md)
- [FirstMatchStrategy](FirstMatchStrategy.md)
- [TaggedStrategy](TaggedStrategy.md)
- [CleanerChain](CleanerChain.md)
