# ChainOfResponsibilityTrait

## Overview

`ChainOfResponsibilityTrait` provides common functionality for Chain of Responsibility pattern implementations.
It manages priority queues and handler registration for both `TranscoderChain` and `DetectorChain`.

**Namespace:** `Ducks\Component\EncodingRepair\Traits`

## Purpose

This trait eliminates code duplication between chain classes by providing:

- Generic priority queue management
- Automatic queue rebuilding
- Lazy initialization of SplPriorityQueue

## Type Parameters

```php
/**
 * @template T
 */
trait ChainOfResponsibilityTrait
```

The trait uses PHP template annotations for type safety, where `T` represents the handler type
(e.g., `TranscoderInterface` or `DetectorInterface`).

## Properties

### $queue

```php
/**
 * @var null|SplPriorityQueue<int, T>
 */
private ?SplPriorityQueue $queue;
```

Priority queue for handlers, lazily initialized.

### $registered

```php
/**
 * @var list<array{handler: T, priority: int}>
 */
private array $registered;
```

List of registered handlers with their priorities.

## Methods

### rebuildQueue()

```php
private function rebuildQueue(): void
```

Rebuilds the priority queue from registered handlers. Called before each chain execution to ensure proper ordering.

### getSplPriorityQueue()

```php
/**
 * @return SplPriorityQueue<int, T>
 */
private function getSplPriorityQueue(): SplPriorityQueue
```

Returns the priority queue, initializing it if necessary.

**Returns:** `SplPriorityQueue` - Priority queue instance

## Used By

- [TranscoderChain](TranscoderChain.md)
- [DetectorChain](DetectorChain.md)

## Example Usage

### In TranscoderChain

```php
<?php

namespace Ducks\Component\EncodingRepair\Transcoder;

use Ducks\Component\EncodingRepair\Traits\ChainOfResponsibilityTrait;

final class TranscoderChain
{
    /**
     * @use ChainOfResponsibilityTrait<TranscoderInterface>
     */
    use ChainOfResponsibilityTrait;

    public function register(TranscoderInterface $transcoder, ?int $priority = null): void
    {
        $finalPriority = $priority ?? $transcoder->getPriority();

        $this->registered[] = [
            'handler' => $transcoder,
            'priority' => $finalPriority,
        ];

        $this->getSplPriorityQueue()->insert($transcoder, $finalPriority);
    }

    public function transcode(string $data, string $to, string $from, array $options): ?string
    {
        $this->rebuildQueue();

        foreach ($this->getSplPriorityQueue() as $transcoder) {
            $result = $transcoder->transcode($data, $to, $from, $options);
            if (null !== $result) {
                return $result;
            }
        }

        return null;
    }
}
```

### In DetectorChain

```php
<?php

namespace Ducks\Component\EncodingRepair\Detector;

use Ducks\Component\EncodingRepair\Traits\ChainOfResponsibilityTrait;

final class DetectorChain
{
    /**
     * @use ChainOfResponsibilityTrait<DetectorInterface>
     */
    use ChainOfResponsibilityTrait;

    public function register(DetectorInterface $detector, ?int $priority = null): void
    {
        $finalPriority = $priority ?? $detector->getPriority();

        $this->registered[] = [
            'handler' => $detector,
            'priority' => $finalPriority,
        ];

        $this->getSplPriorityQueue()->insert($detector, $finalPriority);
    }

    public function detect(string $string, array $options): ?string
    {
        $this->rebuildQueue();

        foreach ($this->getSplPriorityQueue() as $detector) {
            $result = $detector->detect($string, $options);
            if (null !== $result) {
                return $result;
            }
        }

        return null;
    }
}
```

## Design Pattern

This trait implements the **Chain of Responsibility** pattern with priority-based ordering:

1. **Registration:** Handlers are registered with priorities
2. **Rebuilding:** Queue is rebuilt before execution
3. **Execution:** Handlers execute in priority order (highest first)
4. **Short-circuit:** First successful handler returns result
5. **Fallback:** Chain returns null if all handlers fail

## Priority System

Higher priority values execute first:

- **100+:** High-priority handlers (e.g., UConverter, MbStringDetector)
- **50-99:** Medium-priority handlers (e.g., Iconv, FileInfoDetector)
- **0-49:** Low-priority handlers (e.g., MbString)

## Type Safety

The trait uses PHP template annotations for type safety:

```php
/**
 * @template T
 * @use ChainOfResponsibilityTrait<TranscoderInterface>
 */
```

This ensures:

- The queue only contains handlers of the correct type
- Static analysis tools (PHPStan, Psalm) can verify type correctness
- Better IDE autocompletion and type hints

## Benefits

- **DRY Principle:** Eliminates ~40 lines of duplicated code per chain
- **Consistency:** Ensures identical behavior across chains
- **Maintainability:** Single source of truth for queue management
- **Extensibility:** Easy to add new chain types
- **Type Safety:** Generic type annotations for static analysis

## Performance

- **Lazy Initialization:** Queue is only created when needed
- **Efficient Ordering:** SplPriorityQueue provides O(log n) insertion
- **Rebuild Strategy:** Queue is rebuilt before each execution to ensure consistency

## See Also

- [DetectorChain](DetectorChain.md)
- [ChainOfResponsibilityTrait](ChainOfResponsibilityTrait.md)
- [CallableAdapterTrait](CallableAdapterTrait.md)
- [Chain of Responsibility Pattern](https://refactoring.guru/design-patterns/chain-of-responsibility)
