# CleanerInterface

Contract for string cleaning strategies.

## Namespace

```php
Ducks\Component\EncodingRepair\Cleaner
```

## Interface

```php
interface CleanerInterface extends PrioritizedHandlerInterface
{
    public function clean(string $data, string $encoding, array $options): ?string;
    public function isAvailable(): bool;
}
```

## Methods

### clean()

Cleans invalid sequences from string.

```php
public function clean(string $data, string $encoding, array $options): ?string
```

**Parameters:**

- `$data` (string) - String to clean
- `$encoding` (string) - Target encoding for validation
- `$options` (array<string, mixed>) - Cleaning options

**Returns:** `?string` - Cleaned string or null if cleaner cannot handle

### isAvailable()

Check if cleaner is available on current system.

```php
public function isAvailable(): bool
```

**Returns:** `bool` - True if available

### getPriority()

Get cleaner priority (higher = executed first).

```php
public function getPriority(): int
```

**Returns:** `int` - Priority value

## Example Implementation

```php
use Ducks\Component\EncodingRepair\Cleaner\CleanerInterface;

class CustomCleaner implements CleanerInterface
{
    public function clean(string $data, string $encoding, array $options): ?string
    {
        // Only handle UTF-8
        if ('UTF-8' !== strtoupper($encoding)) {
            return null;
        }

        // Remove non-printable characters
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
- [String Cleaners Guide](../guide/cleaners.md)
- [PrioritizedHandlerInterface](PrioritizedHandlerInterface.md)
