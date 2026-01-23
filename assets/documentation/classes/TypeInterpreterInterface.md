# TypeInterpreterInterface

Contract for type-specific data interpreters implementing Strategy pattern.

## Namespace

`Ducks\Component\EncodingRepair\Interpreter`

## Methods

### supports()

```php
public function supports($data): bool
```

Check if this interpreter supports the given data type.

**Parameters:**
- `$data` (mixed) - Data to check

**Returns:** `bool` - True if supported

### interpret()

```php
public function interpret($data, callable $transcoder, array $options)
```

Interpret and process the data using the transcoder callback.

**Parameters:**
- `$data` (mixed) - Data to process
- `$transcoder` (callable) - Transcoding callback
- `$options` (array<string, mixed>) - Processing options

**Returns:** `mixed` - Processed data

### getPriority()

```php
public function getPriority(): int
```

Get interpreter priority (higher = executed first).

**Returns:** `int` - Priority value

## Implementations

- [`StringInterpreter`](StringInterpreter.md) - Priority: 100
- [`ArrayInterpreter`](ArrayInterpreter.md) - Priority: 50
- [`ObjectInterpreter`](ObjectInterpreter.md) - Priority: 30

## Example

```php
use Ducks\Component\EncodingRepair\Interpreter\TypeInterpreterInterface;

class ResourceInterpreter implements TypeInterpreterInterface
{
    public function supports($data): bool
    {
        return \is_resource($data);
    }

    public function interpret($data, callable $transcoder, array $options)
    {
        $content = \stream_get_contents($data);
        $converted = $transcoder($content);
        
        $newResource = \fopen('php://memory', 'r+');
        \fwrite($newResource, $converted);
        \rewind($newResource);
        
        return $newResource;
    }

    public function getPriority(): int
    {
        return 80;
    }
}

$processor->registerInterpreter(new ResourceInterpreter(), 80);
```
