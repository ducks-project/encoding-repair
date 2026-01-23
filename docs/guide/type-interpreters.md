# Type Interpreter System

The Type Interpreter System provides optimized, type-specific transcoding strategies using the Strategy + Visitor pattern.

## Overview

Instead of manual type checking, the system delegates processing to specialized interpreters:

```php
// Before (manual)
if (is_string($data)) {
    return $transcoder($data);
} elseif (is_array($data)) {
    return array_map(fn($item) => $this->process($item), $data);
} elseif (is_object($data)) {
    // Complex object processing...
}

// After (interpreters)
return $this->interpreterChain->interpret($data, $transcoder, $options);
```

## Built-in Interpreters

### StringInterpreter (Priority: 100)

Direct string processing with minimal overhead.

```php
$processor = new CharsetProcessor();
$result = $processor->toUtf8('Café'); // Uses StringInterpreter
```

### ArrayInterpreter (Priority: 50)

Recursive array processing.

```php
$data = ['name' => 'José', 'city' => 'São Paulo'];
$result = $processor->toUtf8($data); // Uses ArrayInterpreter
```

### ObjectInterpreter (Priority: 30)

Object processing with optional custom property mapping.

```php
$obj = new stdClass();
$obj->name = 'Café';
$result = $processor->toUtf8($obj); // Uses ObjectInterpreter
```

## Custom Property Mappers

### Basic Example

```php
use Ducks\Component\EncodingRepair\Interpreter\PropertyMapperInterface;

class UserMapper implements PropertyMapperInterface
{
    public function map(object $object, callable $transcoder, array $options): object
    {
        $copy = clone $object;
        $copy->name = $transcoder($object->name);
        $copy->email = $transcoder($object->email);
        // password NOT transcoded
        return $copy;
    }
}

$processor = new CharsetProcessor();
$processor->registerPropertyMapper(User::class, new UserMapper());
```

### Performance Benefits

For objects with 50 properties where only 2 need conversion:

- **Without mapper**: 180ms (1000 iterations)
- **With mapper**: 72ms (1000 iterations)
- **Improvement**: 60% faster

## Custom Type Interpreters

### Resource Example

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

## Best Practices

### 1. Use Property Mappers for Large Objects

```php
// BAD: Processes all 100 properties
$result = $processor->toUtf8($largeObject);

// GOOD: Process only 3 needed properties (60% faster)
$processor->registerPropertyMapper(LargeClass::class, new SelectiveMapper());
$result = $processor->toUtf8($largeObject);
```

### 2. Security: Exclude Sensitive Properties

```php
class SecureMapper implements PropertyMapperInterface
{
    public function map(object $object, callable $transcoder, array $options): object
    {
        $copy = clone $object;
        $copy->username = $transcoder($object->username);
        // Skip: password, apiKey, token
        return $copy;
    }
}
```

### 3. Performance: Skip Binary Data

```php
class MediaMapper implements PropertyMapperInterface
{
    public function map(object $object, callable $transcoder, array $options): object
    {
        $copy = clone $object;
        $copy->title = $transcoder($object->title);
        // Skip: $object->imageData (binary)
        return $copy;
    }
}
```

## API Reference

### CharsetProcessor Methods

#### registerInterpreter()

```php
public function registerInterpreter(
    TypeInterpreterInterface $interpreter,
    ?int $priority = null
): self
```

#### registerPropertyMapper()

```php
public function registerPropertyMapper(
    string $className,
    PropertyMapperInterface $mapper
): self
```

#### resetInterpreters()

```php
public function resetInterpreters(): self
```

## See Also

- [TypeInterpreterInterface](../api/TypeInterpreterInterface.md)
- [PropertyMapperInterface](../api/PropertyMapperInterface.md)
- [InterpreterChain](../api/InterpreterChain.md)
- [Advanced Usage](advanced-usage.md)
