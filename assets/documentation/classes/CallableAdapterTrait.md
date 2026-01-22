# CallableAdapterTrait

(PHP 7 >= 7.4.0, PHP 8)

## Introduction

CallableAdapterTrait provides common functionality for callable adapter classes.
It centralizes priority management, availability checking, and reflection utilities
used by both CallableTranscoder and CallableDetector.

**Namespace:** `Ducks\Component\EncodingRepair\Traits`

## Trait Synopsis

```php
trait CallableAdapterTrait {
    /* Properties */
    private int $priority;

    /* Methods */
    public getPriority(): int
    public isAvailable(): bool
    private static getReflection(callable $callable): ReflectionFunctionAbstract
}
```

## Features

- Centralized priority management
- Consistent availability checking
- Reflection utilities for callable validation
- Reduces code duplication across adapter classes

## Properties

### $priority

```php
private int $priority;
```

Execution priority for the adapter.

## Methods

### getPriority

```php
public getPriority(): int
```

Returns the execution priority.

**Returns:** Priority value

### isAvailable

```php
public isAvailable(): bool
```

Checks if the adapter is available for use.

**Returns:** Always `true` for callable adapters

### getReflection

```php
private static getReflection(callable $callable): ReflectionFunctionAbstract
```

Gets reflection information for a callable.

**Parameters:**

- `$callable` - Callable to reflect

**Returns:** ReflectionFunctionAbstract instance

**Throws:** `ReflectionException` if reflection fails

## Used By

- [CallableTranscoder](./CallableTranscoder.md)
- [CallableDetector](./CallableDetector.md)

## Example

```php
<?php

namespace Ducks\Component\EncodingRepair\Transcoder;

use Ducks\Component\EncodingRepair\Traits\CallableAdapterTrait;

final class CallableTranscoder implements TranscoderInterface
{
    use CallableAdapterTrait;

    private $callable;

    public function __construct(callable $callable, int $priority)
    {
        $this->callable = $callable;
        $this->priority = $priority;
    }

    // getPriority() and isAvailable() provided by trait
}
```

## See Also

- [CallableTranscoder](./CallableTranscoder.md)
- [CallableDetector](./CallableDetector.md)
- [ChainOfResponsibilityTrait](./ChainOfResponsibilityTrait.md)
