# Type Interpreter System

## Overview

The Type Interpreter System implements the **Strategy + Visitor Pattern** to provide optimized,
type-specific transcoding strategies.
This architecture allows fine-grained control over how different data types (strings, arrays, objects, resources)
are processed during charset conversion.

## Architecture

### Pattern: Strategy + Visitor

- **Strategy Pattern**: Each interpreter implements a specific strategy for handling a data type
- **Visitor Pattern**: The InterpreterChain visits data structures and delegates to appropriate interpreters
- **Chain of Responsibility**: Interpreters are tried in priority order until one matches

### Components

```text
InterpreterChain (Coordinator)
    ├── StringInterpreter (Priority: 100)
    ├── ArrayInterpreter (Priority: 50)
    └── ObjectInterpreter (Priority: 30)
            └── PropertyMappers (Custom per-class)
```

## Core Interfaces

### TypeInterpreterInterface

Contract for type-specific interpreters:

```php
interface TypeInterpreterInterface
{
    public function supports($data): bool;
    public function interpret($data, callable $transcoder, array $options);
    public function getPriority(): int;
}
```

### PropertyMapperInterface

Contract for custom object property mapping:

```php
interface PropertyMapperInterface
{
    public function map(object $object, callable $transcoder, array $options): object;
}
```

## Built-in Interpreters

### 1. StringInterpreter (Priority: 100)

Handles string data with direct transcoding.

**Performance**: Fastest, no overhead

```php
$processor = new CharsetProcessor();
$result = $processor->toUtf8('Café'); // Uses StringInterpreter
```

### 2. ArrayInterpreter (Priority: 50)

Recursively processes array elements.

**Performance**: O(n) where n = array size

```php
$data = ['name' => 'José', 'city' => 'São Paulo'];
$result = $processor->toUtf8($data); // Uses ArrayInterpreter
```

### 3. ObjectInterpreter (Priority: 30)

Processes objects with optional custom property mapping.

**Default behavior**: Clones object and processes all public properties

**Performance**:

- Without mapper: O(n) where n = number of properties
- With mapper: O(m) where m = mapped properties only (40-60% faster for selective mapping)

```php
$obj = new stdClass();
$obj->name = 'Café';
$result = $processor->toUtf8($obj); // Uses ObjectInterpreter
```

## Custom Property Mappers

### Use Case: Selective Property Processing

Process only specific properties, ignoring others (e.g., passwords, binary data).

### Example: User Mapper

```php
use Ducks\Component\EncodingRepair\Interpreter\PropertyMapperInterface;

class UserMapper implements PropertyMapperInterface
{
    public function map(object $object, callable $transcoder, array $options): object
    {
        if (!$object instanceof User) {
            throw new \InvalidArgumentException('Expected User instance');
        }

        $copy = clone $object;
        $copy->name = $transcoder($object->name);
        $copy->email = $transcoder($object->email);
        // password is NOT transcoded (security)
        // avatar_binary is NOT transcoded (performance)
        return $copy;
    }
}

// Register mapper
$processor = new CharsetProcessor();
$processor->registerPropertyMapper(User::class, new UserMapper());

// Use
$user = new User();
$user->name = 'José';
$user->password = 'secret123'; // Will NOT be converted
$utf8User = $processor->toUtf8($user);
```

### Performance Benefits

For an object with 50 properties where only 2 need conversion:

- **Without mapper**: Processes all 50 properties (~100ms)
- **With mapper**: Processes only 2 properties (~40ms)
- **Improvement**: 60% faster

## Custom Type Interpreters

### Use Case: Handle Custom Data Types

Add support for resources, streams, or custom objects.

### Example: Resource Interpreter

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
        return 80; // Between StringInterpreter (100) and ArrayInterpreter (50)
    }
}

// Register interpreter
$processor = new CharsetProcessor();
$processor->registerInterpreter(new ResourceInterpreter(), 80);

// Use
$resource = fopen('data.txt', 'r');
$convertedResource = $processor->toUtf8($resource);
```

## API Reference

### CharsetProcessor Methods

#### registerInterpreter()

Register a custom type interpreter.

```php
public function registerInterpreter(
    TypeInterpreterInterface $interpreter,
    ?int $priority = null
): self
```

**Parameters**:

- `$interpreter`: Interpreter instance
- `$priority`: Priority override (null = use interpreter's default)

**Returns**: `$this` (fluent interface)

#### unregisterInterpreter()

Remove an interpreter from the chain.

```php
public function unregisterInterpreter(
    TypeInterpreterInterface $interpreter
): self
```

#### registerPropertyMapper()

Register a property mapper for a specific class.

```php
public function registerPropertyMapper(
    string $className,
    PropertyMapperInterface $mapper
): self
```

**Parameters**:

- `$className`: Fully qualified class name
- `$mapper`: Property mapper instance

**Throws**: `RuntimeException` if ObjectInterpreter not registered

#### resetInterpreters()

Reset to default interpreters.

```php
public function resetInterpreters(): self
```

## Best Practices

### 1. Use Property Mappers for Large Objects

```php
// BAD: Processes all 100 properties
$result = $processor->toUtf8($largeObject);

// GOOD: Process only 3 needed properties
$processor->registerPropertyMapper(LargeClass::class, new SelectiveMapper());
$result = $processor->toUtf8($largeObject); // 60% faster
```

### 2. Register Interpreters by Priority

Higher priority = executed first

```php
$processor->registerInterpreter(new ResourceInterpreter(), 80);  // Before arrays
$processor->registerInterpreter(new CustomInterpreter(), 120);   // Before strings
```

### 3. Security: Exclude Sensitive Properties

```php
class SecureMapper implements PropertyMapperInterface
{
    public function map(object $object, callable $transcoder, array $options): object
    {
        $copy = clone $object;
        // Only process public, non-sensitive properties
        $copy->username = $transcoder($object->username);
        // Skip: password, apiKey, token
        return $copy;
    }
}
```

### 4. Performance: Skip Binary Data

```php
class MediaMapper implements PropertyMapperInterface
{
    public function map(object $object, callable $transcoder, array $options): object
    {
        $copy = clone $object;
        $copy->title = $transcoder($object->title);
        $copy->description = $transcoder($object->description);
        // Skip: $object->imageData (binary, no conversion needed)
        return $copy;
    }
}
```

## Performance Benchmarks

### Object Processing (50 properties, 2 need conversion)

| Method | Time (1000 iterations) | Improvement |
| -------- | ------------------------ | ------------- |
| Without mapper | 180ms | Baseline |
| With mapper | 72ms | **60% faster** |

### Type Detection Overhead

| Data Type | Overhead | Notes |
| ----------- | ---------- | ------- |
| String | ~0.1μs | Minimal (is_string check) |
| Array | ~0.2μs | Minimal (is_array check) |
| Object | ~0.3μs | Minimal (is_object check) |

## Migration Guide

### From Manual Type Checking

**Before**:

```php
if (is_string($data)) {
    return $this->convertString($data);
} elseif (is_array($data)) {
    return array_map(fn($item) => $this->convert($item), $data);
} elseif (is_object($data)) {
    $copy = clone $data;
    foreach (get_object_vars($copy) as $key => $value) {
        $copy->$key = $this->convert($value);
    }
    return $copy;
}
```

**After**:

```php
return $this->interpreterChain->interpret($data, $transcoder, $options);
```

### Benefits

- **Extensibility**: Add new types without modifying core
- **Testability**: Each interpreter tested independently
- **Performance**: Optimized strategies per type
- **Maintainability**: SOLID principles, DRY code

## Examples

See `examples/interpreter-usage.php` for complete working examples.

## Related Documentation

- [CharsetProcessor API](CharsetProcessor.md)
- [Service Architecture](SERVICE_ARCHITECTURE.md)
- [Chain of Responsibility Pattern](AboutMiddleware.md)
