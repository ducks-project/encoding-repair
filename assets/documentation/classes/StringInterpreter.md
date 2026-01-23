# StringInterpreter

Optimized interpreter for string data.

## Namespace

`Ducks\Component\EncodingRepair\Interpreter`

## Implements

- [`TypeInterpreterInterface`](TypeInterpreterInterface.md)

## Priority

**100** (highest - executed first)

## Methods

### supports()

```php
public function supports($data): bool
```

Returns `true` if `$data` is a string.

### interpret()

```php
public function interpret($data, callable $transcoder, array $options)
```

Directly applies the transcoder callback to the string.

### getPriority()

```php
public function getPriority(): int
```

Returns `100`.

## Performance

- **Overhead**: ~0.1μs (minimal)
- **Strategy**: Direct callback invocation, no recursion

## Example

```php
$interpreter = new StringInterpreter();

$transcoder = fn($value) => strtoupper($value);
$result = $interpreter->interpret('hello', $transcoder, []);
// Result: 'HELLO'
```

## Related

- [`ArrayInterpreter`](ArrayInterpreter.md)
- [`ObjectInterpreter`](ObjectInterpreter.md)
