# InterpreterChain

Chain of Responsibility coordinator for type interpreters.

## Namespace

`Ducks\Component\EncodingRepair\Interpreter`

## Synopsis

```php
final class InterpreterChain
{
    public function interpret($data, callable $transcoder, array $options);
    public function getObjectInterpreter(): ?ObjectInterpreter;
}
```

## Methods

### interpret()

Interpret data using the first matching interpreter.

```php
public function interpret($data, callable $transcoder, array $options)
```

**Parameters:**

- `$data` (mixed) - Data to interpret
- `$transcoder` (callable) - Transcoding callback
- `$options` (array) - Processing options

**Returns:** `mixed` - Interpreted data

**Behavior:**

1. Iterates through registered interpreters by priority (highest first)
2. Calls `supports()` on each interpreter
3. First matching interpreter processes the data
4. Returns original data if no interpreter matches

### getObjectInterpreter()

Get the ObjectInterpreter from the chain if registered.

```php
public function getObjectInterpreter(): ?ObjectInterpreter
```

**Returns:** `ObjectInterpreter|null`

## Default Configuration

```php
$chain = new InterpreterChain();
$chain->register(new StringInterpreter(), 100);
$chain->register(new ArrayInterpreter($chain), 50);
$chain->register(new ObjectInterpreter($chain), 30);
```

## Example

```php
$chain = new InterpreterChain();
$chain->register(new StringInterpreter(), 100);
$chain->register(new CustomInterpreter(), 80);

$transcoder = fn($value) => strtoupper($value);
$result = $chain->interpret('hello', $transcoder, []);
// Uses StringInterpreter (priority 100)
```

## See Also

- [TypeInterpreterInterface](TypeInterpreterInterface.md)
- [ChainOfResponsibilityTrait](ChainOfResponsibilityTrait.md)
- [Type Interpreter System](../guide/type-interpreters.md)
