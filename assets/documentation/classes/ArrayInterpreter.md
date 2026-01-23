# ArrayInterpreter

Recursive interpreter for array data.

## Namespace

`Ducks\Component\EncodingRepair\Interpreter`

## Implements

- [`TypeInterpreterInterface`](TypeInterpreterInterface.md)

## Priority

**50** (medium)

## Constructor

```php
public function __construct(InterpreterChain $chain)
```

**Parameters:**
- `$chain` (InterpreterChain) - Chain for recursive interpretation

## Methods

### supports()

```php
public function supports($data): bool
```

Returns `true` if `$data` is an array.

### interpret()

```php
public function interpret($data, callable $transcoder, array $options)
```

Recursively processes array elements using the interpreter chain.

### getPriority()

```php
public function getPriority(): int
```

Returns `50`.

## Performance

- **Overhead**: ~0.2μs per element
- **Complexity**: O(n) where n = array size
- **Strategy**: `array_map` with recursive chain interpretation

## Example

```php
$chain = new InterpreterChain();
$chain->register(new StringInterpreter(), 100);
$interpreter = new ArrayInterpreter($chain);

$transcoder = fn($value) => strtoupper($value);
$data = ['hello', 'world'];
$result = $interpreter->interpret($data, $transcoder, []);
// Result: ['HELLO', 'WORLD']
```

## Nested Arrays

```php
$data = [
    'name' => 'José',
    'items' => ['café', 'thé'],
];
$result = $processor->toUtf8($data);
// Recursively processes all levels
```

## Related

- [`StringInterpreter`](StringInterpreter.md)
- [`ObjectInterpreter`](ObjectInterpreter.md)
