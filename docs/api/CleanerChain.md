# CleanerChain

Chain of Responsibility for string cleaners.

## Namespace

```php
Ducks\Component\EncodingRepair\Cleaner
```

## Class

```php
final class CleanerChain
{
    public function register(CleanerInterface $cleaner, ?int $priority = null): void;
    public function unregister(CleanerInterface $cleaner): void;
    public function clean(string $data, string $encoding, array $options): ?string;
}
```

## Methods

### register()

Register a cleaner with optional priority override.

```php
public function register(CleanerInterface $cleaner, ?int $priority = null): void
```

**Parameters:**

- `$cleaner` (CleanerInterface) - Cleaner instance
- `$priority` (?int) - Priority override (null = use cleaner's default)

### unregister()

Unregister a cleaner from the chain.

```php
public function unregister(CleanerInterface $cleaner): void
```

**Parameters:**

- `$cleaner` (CleanerInterface) - Cleaner instance to remove

### clean()

Cleans string using registered cleaners.

```php
public function clean(string $data, string $encoding, array $options): ?string
```

**Parameters:**

- `$data` (string) - String to clean
- `$encoding` (string) - Target encoding
- `$options` (array<string, mixed>) - Cleaning options

**Returns:** `?string` - Cleaned string or null if no cleaner succeeded

## Usage

```php
use Ducks\Component\EncodingRepair\Cleaner\CleanerChain;
use Ducks\Component\EncodingRepair\Cleaner\MbScrubCleaner;
use Ducks\Component\EncodingRepair\Cleaner\PregMatchCleaner;
use Ducks\Component\EncodingRepair\Cleaner\IconvCleaner;

$chain = new CleanerChain();
$chain->register(new MbScrubCleaner());
$chain->register(new PregMatchCleaner());
$chain->register(new IconvCleaner());

$cleaned = $chain->clean($corruptedString, 'UTF-8', []);
```

## Execution Order

Cleaners are executed in priority order (highest first):

1. **MbScrubCleaner** (100)
2. **PregMatchCleaner** (50)
3. **IconvCleaner** (10)

Chain stops at first non-null result.

## See Also

- [CleanerInterface](CleanerInterface.md)
- [String Cleaners Guide](../guide/cleaners.md)
- [CharsetProcessor](CharsetProcessor.md)
