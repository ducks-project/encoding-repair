# ObjectInterpreter

Interpreter for object data with custom property mapping support.

## Namespace

`Ducks\Component\EncodingRepair\Interpreter`

## Implements

- [`TypeInterpreterInterface`](TypeInterpreterInterface.md)

## Priority

**30** (lowest)

## Constructor

```php
public function __construct(InterpreterChain $chain)
```

**Parameters:**
- `$chain` (InterpreterChain) - Chain for recursive interpretation

## Methods

### registerMapper()

```php
public function registerMapper(string $className, PropertyMapperInterface $mapper): void
```

Register a custom property mapper for a specific class.

**Parameters:**
- `$className` (string) - Fully qualified class name
- `$mapper` (PropertyMapperInterface) - Property mapper instance

### supports()

```php
public function supports($data): bool
```

Returns `true` if `$data` is an object.

### interpret()

```php
public function interpret($data, callable $transcoder, array $options)
```

Processes object using custom mapper if registered, otherwise uses default mapping.

**Behavior:**
1. Check if custom mapper registered for object's class
2. If yes: Use custom mapper
3. If no: Clone object and process all public properties

### getPriority()

```php
public function getPriority(): int
```

Returns `30`.

## Default Mapping

Without custom mapper:
```php
$copy = clone $object;
foreach (get_object_vars($copy) as $key => $value) {
    $copy->$key = $chain->interpret($value, $transcoder, $options);
}
return $copy;
```

## Performance

### Without Custom Mapper
- **Complexity**: O(n) where n = number of properties
- **Time**: ~180ms for 50 properties (1000 iterations)

### With Custom Mapper
- **Complexity**: O(m) where m = mapped properties only
- **Time**: ~72ms for 2 properties (1000 iterations)
- **Improvement**: 60% faster

## Example: Default Mapping

```php
$obj = new stdClass();
$obj->name = 'José';
$obj->price = 10;

$result = $processor->toUtf8($obj);
// Processes all public properties
```

## Example: Custom Mapper

```php
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

$interpreter = new ObjectInterpreter($chain);
$interpreter->registerMapper(User::class, new UserMapper());
```

## Use Cases

1. **Security**: Exclude sensitive properties (passwords, tokens)
2. **Performance**: Skip large binary data
3. **Validation**: Apply custom logic per property
4. **Selective Processing**: Convert only needed properties

## Related

- [`PropertyMapperInterface`](PropertyMapperInterface.md)
- [`CharsetProcessor::registerPropertyMapper()`](CharsetProcessor.md#registerpropertymapper)
