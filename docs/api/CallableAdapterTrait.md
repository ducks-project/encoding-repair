# CallableAdapterTrait

## Overview

`CallableAdapterTrait` provides common functionality for callable adapter classes in the EncodingRepair package. It centralizes priority management, availability checking, and reflection utilities used by both `CallableTranscoder` and `CallableDetector`.

**Namespace:** `Ducks\Component\EncodingRepair\Traits`

## Purpose

This trait eliminates code duplication between adapter classes by providing:

- Priority management
- Availability checking
- Reflection utilities for callable validation

## Properties

### $priority

```php
private int $priority;
```

Stores the execution priority for the adapter.

## Methods

### getPriority()

```php
public function getPriority(): int
```

Returns the execution priority.

**Returns:** `int` - Priority value

### isAvailable()

```php
public function isAvailable(): bool
```

Checks if the adapter is available for use.

**Returns:** `bool` - Always `true` for callable adapters

### getReflection()

```php
private static function getReflection(callable $callable): ReflectionFunctionAbstract
```

Gets reflection information for a callable.

**Parameters:**

- `$callable` - Callable to reflect

**Returns:** `ReflectionFunctionAbstract` - Reflection instance

**Throws:** `ReflectionException` - If reflection fails

## Used By

- [CallableTranscoder](CallableTranscoder.md)
- [CallableDetector](CallableDetector.md)

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

## Benefits

- **DRY Principle:** Eliminates ~40 lines of duplicated code
- **Consistency:** Ensures identical behavior across adapters
- **Maintainability:** Single source of truth for common functionality
- **Type Safety:** Proper type hints and return types

## See Also

- [CallableTranscoder](CallableTranscoder.md)
- [CallableDetector](CallableDetector.md)
- [ChainOfResponsibilityTrait](ChainOfResponsibilityTrait.md)
