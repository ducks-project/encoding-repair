# ChainOfResponsibilityTrait

(PHP 7 >= 7.4.0, PHP 8)

## Introduction

ChainOfResponsibilityTrait provides common functionality for Chain of Responsibility pattern implementations.
It manages priority queues and handler registration for both TranscoderChain and DetectorChain.

**Namespace:** `Ducks\Component\EncodingRepair\Traits`

## Trait Synopsis

```php
/**
 * @template T
 */
trait ChainOfResponsibilityTrait {
    /* Properties */
    private ?SplPriorityQueue $queue;
    private array $registered;

    /* Methods */
    private rebuildQueue(): void
    private getSplPriorityQueue(): SplPriorityQueue
}
```

## Features

- Generic priority queue management using PHP templates
- Automatic queue rebuilding for iteration
- Lazy initialization of SplPriorityQueue
- Reduces code duplication across chain classes

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

### rebuildQueue

```php
private rebuildQueue(): void
```

Rebuilds the priority queue from registered handlers.
Called before each chain execution to ensure proper ordering.

### getSplPriorityQueue

```php
/**
 * @return SplPriorityQueue<int, T>
 */
private getSplPriorityQueue(): SplPriorityQueue
```

Returns the priority queue, initializing it if necessary.

**Returns:** SplPriorityQueue instance

## Used By

- [TranscoderChain](./TranscoderChain.md)
- [DetectorChain](./DetectorChain.md)

## Example

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

## Design Pattern

This trait implements the **Chain of Responsibility** pattern with priority-based ordering:

1. Handlers are registered with priorities
2. Queue is rebuilt before execution
3. Handlers execute in priority order (highest first)
4. First successful handler returns result
5. Chain returns null if all handlers fail

## Type Safety

The trait uses PHP template annotations for type safety:

```php
/**
 * @template T
 * @use ChainOfResponsibilityTrait<TranscoderInterface>
 */
```

This ensures the queue only contains handlers of the correct type.

## See Also

- [TranscoderChain](./TranscoderChain.md)
- [DetectorChain](./DetectorChain.md)
- [CallableAdapterTrait](./CallableAdapterTrait.md)
