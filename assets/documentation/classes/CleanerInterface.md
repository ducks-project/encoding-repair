# CleanerInterface

Contract for string cleaning strategies.

## Namespace

`Ducks\Component\EncodingRepair\Cleaner`

## Description

Cleaners remove invalid byte sequences from strings before encoding conversion.
They follow the Chain of Responsibility pattern with priority-based execution.

## Methods

### clean()

```php
public function clean(string $data, string $encoding, array $options): ?string
```

Cleans invalid sequences from string.

**Parameters:**
- `$data` (string) - String to clean
- `$encoding` (string) - Target encoding for validation
- `$options` (array) - Cleaning options

**Returns:** `?string` - Cleaned string or null if cleaner cannot handle

### getPriority()

```php
public function getPriority(): int
```

Get cleaner priority (higher = executed first).

**Returns:** `int` - Priority value

### isAvailable()

```php
public function isAvailable(): bool
```

Check if cleaner is available on current system.

**Returns:** `bool` - True if available

## Example

```php
use Ducks\Component\EncodingRepair\Cleaner\CleanerInterface;

class CustomCleaner implements CleanerInterface
{
    public function clean(string $data, string $encoding, array $options): ?string
    {
        return preg_replace('/[^\x20-\x7E]/', '', $data);
    }

    public function getPriority(): int
    {
        return 75;
    }

    public function isAvailable(): bool
    {
        return true;
    }
}
```

## See Also

- [CleanerChain](CleanerChain.md)
- [MbScrubCleaner](MbScrubCleaner.md)
- [PregMatchCleaner](PregMatchCleaner.md)
- [IconvCleaner](IconvCleaner.md)
