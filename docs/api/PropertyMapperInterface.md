# PropertyMapperInterface

Contract for custom object property mapping.

## Namespace

`Ducks\Component\EncodingRepair\Interpreter`

## Synopsis

```php
interface PropertyMapperInterface
{
    public function map(object $object, callable $transcoder, array $options): object;
}
```

## Methods

### map()

Map object properties using the transcoder callback.

```php
public function map(object $object, callable $transcoder, array $options): object
```

**Parameters:**

- `$object` (object) - Object to map
- `$transcoder` (callable) - Transcoding callback
- `$options` (array) - Processing options

**Returns:** `object` - Cloned object with mapped properties

## Use Cases

- **Selective Processing**: Convert only specific properties
- **Security**: Exclude sensitive data (passwords, tokens)
- **Performance**: Skip binary data or large properties (60% faster)
- **Validation**: Apply custom logic per property

## Example

```php
use Ducks\Component\EncodingRepair\Interpreter\PropertyMapperInterface;

class UserMapper implements PropertyMapperInterface
{
    public function map(object $object, callable $transcoder, array $options): object
    {
        $copy = clone $object;
        $copy->name = $transcoder($object->name);
        $copy->email = $transcoder($object->email);
        // password is NOT transcoded (security)
        return $copy;
    }
}

$processor->registerPropertyMapper(User::class, new UserMapper());
```

## Performance

For objects with 50 properties where only 2 need conversion:

- **Without mapper**: ~180ms (1000 iterations)
- **With mapper**: ~72ms (1000 iterations)
- **Improvement**: 60% faster

## See Also

- [ObjectInterpreter](ObjectInterpreter.md)
- [CharsetProcessor::registerPropertyMapper()](CharsetProcessor.md#registerpropertymapper)
- [Type Interpreter System](../guide/type-interpreters.md)
