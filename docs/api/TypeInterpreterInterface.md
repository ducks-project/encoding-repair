# TypeInterpreterInterface

Contract for type-specific data interpreters implementing Strategy pattern.

## Namespace

`Ducks\Component\EncodingRepair\Interpreter`

## Synopsis

```php
interface TypeInterpreterInterface
{
    public function supports($data): bool;
    public function interpret($data, callable $transcoder, array $options);
    public function getPriority(): int;
}
```

## Methods

### supports()

Check if this interpreter supports the given data type.

```php
public function supports($data): bool
```

**Parameters:**

- `$data` (mixed) - Data to check

**Returns:** `bool` - True if supported

### interpret()

Interpret and process the data using the transcoder callback.

```php
public function interpret($data, callable $transcoder, array $options)
```

**Parameters:**

- `$data` (mixed) - Data to process
- `$transcoder` (callable) - Transcoding callback
- `$options` (array) - Processing options

**Returns:** `mixed` - Processed data

### getPriority()

Get interpreter priority (higher = executed first).

```php
public function getPriority(): int
```

**Returns:** `int` - Priority value

## Built-in Implementations

- [StringInterpreter](StringInterpreter.md) - Priority: 100
- [ArrayInterpreter](ArrayInterpreter.md) - Priority: 50
- [ObjectInterpreter](ObjectInterpreter.md) - Priority: 30

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

## See Also

- [InterpreterChain](InterpreterChain.md)
- [PropertyMapperInterface](PropertyMapperInterface.md)
- [Type Interpreter System](../guide/type-interpreters.md)
