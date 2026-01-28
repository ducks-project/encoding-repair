# CleanerChain

Chain of Responsibility for string cleaners.

## Namespace

`Ducks\Component\EncodingRepair\Cleaner`

## Description

Coordinates multiple cleaner strategies with priority-based execution.
Stops at first successful cleaner (returns non-null result).

## Methods

### register()

```php
public function register(CleanerInterface $cleaner, ?int $priority = null): void
```

Register a cleaner with optional priority override.

**Parameters:**
- `$cleaner` (CleanerInterface) - Cleaner instance
- `$priority` (?int) - Priority override (null = use cleaner's default)

### unregister()

```php
public function unregister(CleanerInterface $cleaner): void
```

Unregister a cleaner from the chain.

**Parameters:**
- `$cleaner` (CleanerInterface) - Cleaner instance to remove

### clean()

```php
public function clean(string $data, string $encoding, array $options): ?string
```

Cleans string using registered cleaners.

**Parameters:**
- `$data` (string) - String to clean
- `$encoding` (string) - Target encoding
- `$options` (array) - Cleaning options

**Returns:** `?string` - Cleaned string or null if no cleaner succeeded

## Example

```php
use Ducks\Component\EncodingRepair\Cleaner\CleanerChain;
use Ducks\Component\EncodingRepair\Cleaner\MbScrubCleaner;
use Ducks\Component\EncodingRepair\Cleaner\PregMatchCleaner;

$chain = new CleanerChain();
$chain->register(new MbScrubCleaner());
$chain->register(new PregMatchCleaner());

$cleaned = $chain->clean($corruptedString, 'UTF-8', []);
```

## Priority Order

1. **MbScrubCleaner** (100) - Best quality
2. **PregMatchCleaner** (50) - Fastest
3. **IconvCleaner** (10) - Universal fallback

## See Also

- [CleanerInterface](CleanerInterface.md)
- [CharsetProcessor](CharsetProcessor.md)
