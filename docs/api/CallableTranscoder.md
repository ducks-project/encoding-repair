# CallableTranscoder

(PHP 7 >= 7.4.0, PHP 8)

## Introduction

CallableTranscoder is an adapter that wraps legacy callable functions into the TranscoderInterface. It provides backward compatibility for custom transcoders defined as closures or functions.

**Priority:** User-defined  
**Requires:** None

## Class Synopsis

```php
final class CallableTranscoder implements TranscoderInterface {
    /* Methods */
    public __construct(callable $callable, int $priority)
    public transcode(string $data, string $to, string $from, array $options): ?string
    public getPriority(): int
    public isAvailable(): bool
}
```

## Features

- Wraps callable functions as transcoders
- Validates callable signature at construction
- Validates return type at runtime
- Supports closures, functions, and invokable objects
- Custom priority support

## Methods

### __construct

```php
public __construct(callable $callable, int $priority)
```

Create a new CallableTranscoder.

**Parameters:**
- `$callable` - Function with signature: `fn(string, string, string, array): ?string`
- `$priority` - Execution priority

**Throws:** `InvalidArgumentException` if callable signature is invalid

### transcode

```php
public transcode(string $data, string $to, string $from, array $options): ?string
```

Execute the wrapped callable.

**Throws:** `InvalidArgumentException` if return type is invalid

### getPriority

```php
public getPriority(): int
```

**Returns:** User-defined priority

### isAvailable

```php
public isAvailable(): bool
```

**Returns:** Always `true`

## Example

```php
<?php

use Ducks\Component\EncodingRepair\Transcoder\CallableTranscoder;
use Ducks\Component\EncodingRepair\CharsetHelper;

// Create from closure
$transcoder = new CallableTranscoder(
    function (string $data, string $to, string $from, array $options): ?string {
        if ('CUSTOM' !== $from) {
            return null;
        }
        return customConvert($data, $to);
    },
    75  // Priority
);

// Register
CharsetHelper::registerTranscoder($transcoder);

// Or use shorthand
CharsetHelper::registerTranscoder(
    fn($data, $to, $from, $opts) => customConvert($data, $to),
    75
);
```

## Validation

CallableTranscoder validates:

1. **Signature:** Must accept at least 4 parameters
2. **Return type:** Must return `string|null`

```php
// Valid
$valid = new CallableTranscoder(
    fn(string $d, string $t, string $f, array $o): ?string => null,
    50
);

// Invalid - throws InvalidArgumentException
$invalid = new CallableTranscoder(
    fn(string $d): string => $d,  // Only 1 parameter
    50
);
```

## See Also

- [TranscoderInterface](./TranscoderInterface.md)
- [CharsetHelper::registerTranscoder](../CharsetHelper.registerTranscoder.md)
