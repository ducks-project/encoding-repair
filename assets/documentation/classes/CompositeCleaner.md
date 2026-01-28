# CompositeCleaner

Groups multiple cleaners with a strategy (Decorator pattern).

## Namespace

`Ducks\Component\EncodingRepair\Cleaner`

## Description

Implements the Decorator pattern to group multiple cleaners into a single unit with its own execution strategy. This allows creating reusable cleaner groups and mixing different execution patterns within the same chain.

## Constructor

```php
public function __construct(
    ?CleanerStrategyInterface $strategy = null,
    int $priority = 100,
    CleanerInterface ...$cleaners
)
```

**Parameters:**
- `$strategy` (CleanerStrategyInterface|null) - Execution strategy (default: PipelineStrategy)
- `$priority` (int) - Priority for this composite (default: 100)
- `$cleaners` (CleanerInterface...) - Cleaners to group

## Usage

### Basic Grouping

```php
use Ducks\Component\EncodingRepair\Cleaner\CompositeCleaner;
use Ducks\Component\EncodingRepair\Cleaner\BomCleaner;
use Ducks\Component\EncodingRepair\Cleaner\HtmlEntityCleaner;

// Group related cleaners
$webContentGroup = new CompositeCleaner(
    null,  // PipelineStrategy by default
    150,   // High priority
    new BomCleaner(),
    new HtmlEntityCleaner()
);

$processor->registerCleaner($webContentGroup);
```

### With Custom Strategy

```php
use Ducks\Component\EncodingRepair\Cleaner\FirstMatchStrategy;

// Fallback group with FirstMatch
$fallbackGroup = new CompositeCleaner(
    new FirstMatchStrategy(),
    100,
    new MbScrubCleaner(),
    new PregMatchCleaner()
);
```

### Mixed Usage

```php
// High priority composite
$criticalGroup = new CompositeCleaner(
    new PipelineStrategy(),
    200,
    new BomCleaner(),
    new HtmlEntityCleaner()
);
$processor->registerCleaner($criticalGroup);

// Lower priority individual cleaner
$processor->registerCleaner(new WhitespaceCleaner(), 50);

// Execution order: criticalGroup (BOM+HTML), then WhitespaceCleaner
```

## Use Cases

### 1. Reusable Cleaner Groups

```php
// Define once, use everywhere
$webContentGroup = new CompositeCleaner(
    new PipelineStrategy(),
    150,
    new BomCleaner(),
    new HtmlEntityCleaner(),
    new WhitespaceCleaner()
);

// Use in multiple processors
$webProcessor->registerCleaner($webContentGroup);
$apiProcessor->registerCleaner($webContentGroup);
```

### 2. Nested Composites

```php
$innerGroup = new CompositeCleaner(
    new PipelineStrategy(),
    100,
    new BomCleaner(),
    new HtmlEntityCleaner()
);

$outerGroup = new CompositeCleaner(
    new PipelineStrategy(),
    150,
    $innerGroup,
    new WhitespaceCleaner()
);
```

### 3. Context-Specific Groups

```php
// Import context: basic cleaning only
$importGroup = new CompositeCleaner(
    new FirstMatchStrategy(),
    100,
    new MbScrubCleaner(),
    new PregMatchCleaner()
);

// Display context: comprehensive cleaning
$displayGroup = new CompositeCleaner(
    new PipelineStrategy(),
    150,
    new BomCleaner(),
    new HtmlEntityCleaner(),
    new WhitespaceCleaner()
);
```

## Benefits

### Code Organization

- Group related cleaners logically
- Reusable across different processors
- Clear separation of concerns

### Flexibility

- Each composite has its own strategy
- Mix different execution patterns in same chain
- Nest composites for complex scenarios

### Maintainability

- Define groups once, use everywhere
- Easy to modify group behavior
- Testable in isolation

## Comparison with CleanerChain

| Aspect | CompositeCleaner | CleanerChain |
|--------|------------------|--------------|
| Purpose | Group cleaners | Coordinate all cleaners |
| Strategy | Per composite | Global |
| Reusability | High | Low |
| Nesting | Supported | N/A |
| Use case | Logical grouping | Main coordinator |

## Example: Real-World Scenario

```php
// Define reusable groups
class CleanerGroups
{
    public static function webContent(): CompositeCleaner
    {
        return new CompositeCleaner(
            new PipelineStrategy(),
            150,
            new BomCleaner(),
            new HtmlEntityCleaner(),
            new WhitespaceCleaner()
        );
    }
    
    public static function apiFallback(): CompositeCleaner
    {
        return new CompositeCleaner(
            new FirstMatchStrategy(),
            100,
            new MbScrubCleaner(),
            new PregMatchCleaner()
        );
    }
}

// Use in application
$webProcessor = new CharsetProcessor();
$webProcessor->registerCleaner(CleanerGroups::webContent());

$apiProcessor = new CharsetProcessor();
$apiProcessor->registerCleaner(CleanerGroups::apiFallback());
```

## See Also

- [CleanerInterface](CleanerInterface.md)
- [CleanerStrategyInterface](CleanerStrategyInterface.md)
- [PipelineStrategy](PipelineStrategy.md)
- [FirstMatchStrategy](FirstMatchStrategy.md)
- [CleanerChain](CleanerChain.md)
