# ChainOfResponsibilityTrait

Common functionality for Chain of Responsibility pattern implementations with priority-based handler execution.

## Namespace

```php
Ducks\Component\EncodingRepair\Traits\ChainOfResponsibilityTrait
```

## Description

`ChainOfResponsibilityTrait` provides shared functionality for implementing the Chain of Responsibility pattern with priority-based handler execution. It manages handler registration, priority queue maintenance, and provides a consistent interface for chain implementations.

This trait is used by [TranscoderChain](TranscoderChain.md) and [DetectorChain](DetectorChain.md) to avoid code duplication while maintaining type safety through generics.

## Trait Declaration

```php
/**
 * @template T
 */
trait ChainOfResponsibilityTrait
{
    private ?SplPriorityQueue $queue = null;
    private array $registered = [];
    
    public function register($handler, ?int $priority = null): void;
    private function unregister($handler): void;
    private function rebuildQueue(): void;
    private function getSplPriorityQueue(): SplPriorityQueue;
}
```

## Features

- **Generic type support**: Template-based type safety for handlers
- **Priority management**: Automatic priority-based ordering
- **Lazy initialization**: Queue created only when needed
- **Dynamic registration**: Add/remove handlers at runtime
- **Queue rebuilding**: Automatic queue reconstruction after modifications
- **Code reusability**: Shared logic for all chain implementations

## Type Parameters

```php
/**
 * @template T
 */
```

The trait uses PHP template annotations for type safety, where `T` represents the handler type (e.g., `TranscoderInterface` or `DetectorInterface`).

## Properties

### $queue

```php
/**
 * @var null|SplPriorityQueue<int, T>
 */
private ?SplPriorityQueue $queue = null;
```

Priority queue for handlers, lazily initialized. Set to null after unregister() to force rebuild.

### $registered

```php
/**
 * @var list<array{handler: T, priority: int}>
 */
private array $registered = [];
```

List of registered handlers with their priorities. Used to rebuild the queue when needed.

## Methods

### register()

Register a handler with optional priority override into the chain.

```php
public function register($handler, ?int $priority = null): void
```

**Parameters:**

- `$handler` (T): Handler to register (TranscoderInterface or DetectorInterface)
- `$priority` (int|null): Priority override (null = use handler's getPriority())

**Notes:**

- If priority is null, calls `$handler->getPriority()` to get default priority
- Stores handler in `$registered` array for queue rebuilding
- Inserts handler into priority queue immediately
- Higher priority values execute first

### unregister()

Unregister a handler from the chain.

```php
private function unregister($handler): void
```

**Parameters:**

- `$handler` (T): Handler to remove

**Notes:**

- Removes handler from `$registered` array using strict comparison (===)
- Sets `$queue` to null to force rebuild on next iteration
- Re-indexes array with `array_values()` to maintain list structure
- Does nothing if handler is not found

### rebuildQueue()

Rebuild queue from registered handlers.

```php
private function rebuildQueue(): void
```

**Notes:**

- Creates new SplPriorityQueue instance
- Iterates through `$registered` array
- Inserts each handler with its priority
- Called before each chain execution to ensure proper ordering
- Necessary because SplPriorityQueue is not rewindable after iteration

**Performance:** O(n log n) where n is number of handlers

### getSplPriorityQueue()

Return the queue, initializing if necessary.

```php
/**
 * @return SplPriorityQueue<int, T>
 */
private function getSplPriorityQueue(): SplPriorityQueue
```

**Returns:** SplPriorityQueue instance

**Notes:**

- Lazy initialization: creates queue only when first accessed
- Returns existing queue if already initialized
- Used internally by register() and chain execution methods

## Usage Pattern

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
    use ChainOfResponsibilityTrait {
        ChainOfResponsibilityTrait::register as chainRegister;
        ChainOfResponsibilityTrait::unregister as chainUnregister;
    }

    public function register(TranscoderInterface $transcoder, ?int $priority = null): void
    {
        $this->chainRegister($transcoder, $priority);
    }

    public function unregister(TranscoderInterface $transcoder): void
    {
        $this->chainUnregister($transcoder);
    }

    public function transcode(string $data, string $to, string $from, array $options): ?string
    {
        $this->rebuildQueue();

        foreach ($this->getSplPriorityQueue() as $transcoder) {
            if (!$transcoder->isAvailable()) {
                continue;
            }

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
    use ChainOfResponsibilityTrait {
        ChainOfResponsibilityTrait::register as chainRegister;
        ChainOfResponsibilityTrait::unregister as chainUnregister;
    }

    public function register(DetectorInterface $detector, ?int $priority = null): void
    {
        $this->chainRegister($detector, $priority);
    }

    public function unregister(DetectorInterface $detector): void
    {
        $this->chainUnregister($detector);
    }

    public function detect(string $string, array $options): ?string
    {
        $this->rebuildQueue();

        foreach ($this->getSplPriorityQueue() as $detector) {
            if (!$detector->isAvailable()) {
                continue;
            }

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

The trait implements the **Chain of Responsibility** pattern with priority-based ordering:

1. **Handler Registration**: Handlers are registered with priorities
2. **Queue Building**: Priority queue orders handlers by priority
3. **Sequential Execution**: Handlers execute in order until one succeeds
4. **Early Return**: First successful handler returns result
5. **Fallback**: Returns null if all handlers fail

### Priority Queue Behavior

```text
Priority: 200 → Handler A
Priority: 100 → Handler B
Priority: 100 → Handler C (same priority as B)
Priority: 50  → Handler D

Execution order: A → B → C → D
```

**Note**: Handlers with same priority execute in insertion order.

## Type Safety with Generics

The trait uses PHP template annotations for type safety:

```php
/**
 * @template T
 */
trait ChainOfResponsibilityTrait { }
```

When used in a class:

```php
/**
 * @use ChainOfResponsibilityTrait<TranscoderInterface>
 */
use ChainOfResponsibilityTrait;
```

This ensures:

- `$registered` array only contains TranscoderInterface instances
- `$queue` only contains TranscoderInterface instances
- Static analysis tools (PHPStan, Psalm) can verify type correctness

## Performance

- **Registration**: O(log n) per handler (SplPriorityQueue insertion)
- **Unregistration**: O(n) to filter array + O(1) to invalidate queue
- **Queue Rebuild**: O(n log n) where n is number of handlers
- **Iteration**: O(n) worst case (all handlers tried)
- **Memory**: O(n) for registered array + O(n) for queue

**Optimization tips:**

- Minimize unregister() calls (expensive due to rebuild)
- Register handlers once during initialization
- Use appropriate priorities to favor faster handlers

## Benefits

- **DRY Principle**: Eliminates ~40 lines of duplicated code per chain
- **Consistency**: Ensures identical behavior across chains
- **Maintainability**: Single source of truth for queue management
- **Extensibility**: Easy to add new chain types
- **Type Safety**: Generic type annotations for static analysis

## Thread Safety

ChainOfResponsibilityTrait is **not thread-safe**. Classes using this trait should not be shared between threads without external synchronization.

## See Also

- [TranscoderChain](TranscoderChain.md) - Transcoder chain implementation
- [DetectorChain](DetectorChain.md) - Detector chain implementation
- [TranscoderInterface](TranscoderInterface.md) - Transcoder contract
- [DetectorInterface](DetectorInterface.md) - Detector contract
- [CallableAdapterTrait](CallableAdapterTrait.md) - Callable wrapper trait
- [Chain of Responsibility Pattern](https://refactoring.guru/design-patterns/chain-of-responsibility)
