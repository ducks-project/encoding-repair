# PropertyMapperInterface

Contract for custom object property mapping.

## Namespace

`Ducks\Component\EncodingRepair\Interpreter`

## Methods

### map()

```php
public function map(object $object, callable $transcoder, array $options): object
```

Map object properties using the transcoder callback.

**Parameters:**
- `$object` (object) - Object to map
- `$transcoder` (callable) - Transcoding callback
- `$options` (array<string, mixed>) - Processing options

**Returns:** `object` - Cloned object with mapped properties

## Use Cases

- **Selective Processing**: Convert only specific properties
- **Security**: Exclude sensitive data (passwords, tokens)
- **Performance**: Skip binary data or large properties
- **Validation**: Apply custom logic per property

## Example: User Mapper

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

## Related

- [`ObjectInterpreter`](ObjectInterpreter.md) - Uses property mappers
- [`CharsetProcessor::registerPropertyMapper()`](CharsetProcessor.md#registerpropertymapper)
