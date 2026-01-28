# TaggedStrategy

Strategy that applies only cleaners with matching tags (selective execution).

## Namespace

`Ducks\Component\EncodingRepair\Cleaner`

## Description

Implements selective execution by filtering cleaners based on tags. Only cleaners with tags matching the strategy's tag list are executed. This provides fine-grained control over which cleaners run.

## Constructor

```php
public function __construct(array $tags)
```

**Parameters:**
- `$tags` (array<string>) - Tags to match

## Methods

### registerTags()

Register tags for a cleaner.

```php
public function registerTags(CleanerInterface $cleaner, array $tags): void
```

**Parameters:**
- `$cleaner` (CleanerInterface) - Cleaner instance
- `$tags` (array<string>) - Tags for this cleaner

## Usage

```php
use Ducks\Component\EncodingRepair\Cleaner\CleanerChain;
use Ducks\Component\EncodingRepair\Cleaner\TaggedStrategy;
use Ducks\Component\EncodingRepair\Cleaner\BomCleaner;
use Ducks\Component\EncodingRepair\Cleaner\HtmlEntityCleaner;
use Ducks\Component\EncodingRepair\Cleaner\WhitespaceCleaner;

// Create chain with tagged strategy
$chain = new CleanerChain(new TaggedStrategy(['bom', 'html']));
$chain->register(new BomCleaner(), null, ['bom']);
$chain->register(new HtmlEntityCleaner(), null, ['html']);
$chain->register(new WhitespaceCleaner(), null, ['whitespace']); // Ignored

// Only BOM and HTML cleaners are executed
$result = $chain->clean($data, 'UTF-8', []);
```

## Behavior

1. Iterates through cleaners in priority order
2. Checks if cleaner has any matching tags
3. Skips cleaners without matching tags
4. Applies matching cleaners successively (like PipelineStrategy)
5. Skips unavailable cleaners
6. Returns final result or `null` if no cleaner succeeded

## Use Cases

- **Selective cleaning**: Only apply specific types of cleaners
- **Context-aware processing**: Different tags for different scenarios
- **Fine-grained control**: Enable/disable cleaner groups dynamically

## Example: Context-Aware Cleaning

```php
// Import scenario: only basic cleaning
$importChain = new CleanerChain(new TaggedStrategy(['basic']));
$importChain->register(new BomCleaner(), null, ['basic']);
$importChain->register(new MbScrubCleaner(), null, ['basic']);

// Display scenario: comprehensive cleaning
$displayChain = new CleanerChain(new TaggedStrategy(['basic', 'html', 'whitespace']));
$displayChain->register(new BomCleaner(), null, ['basic']);
$displayChain->register(new HtmlEntityCleaner(), null, ['html']);
$displayChain->register(new WhitespaceCleaner(), null, ['whitespace']);
```

## Tag Matching

Tags are matched using array intersection. A cleaner is executed if it has **at least one** tag matching the strategy's tag list.

```php
// Strategy tags: ['tag1', 'tag2']
// Cleaner tags: ['tag2', 'tag3']
// Result: MATCH (tag2 is common)

// Strategy tags: ['tag1']
// Cleaner tags: ['tag2', 'tag3']
// Result: NO MATCH
```

## Performance

Slightly slower than FirstMatchStrategy due to tag checking, but faster than PipelineStrategy when many cleaners are registered but only a few match.

## See Also

- [CleanerStrategyInterface](CleanerStrategyInterface.md)
- [PipelineStrategy](PipelineStrategy.md)
- [FirstMatchStrategy](FirstMatchStrategy.md)
- [CleanerChain](CleanerChain.md)
