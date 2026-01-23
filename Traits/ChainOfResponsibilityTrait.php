<?php

/**
 * Part of EncodingRepair package.
 *
 * (c) Adrien Loyant <donald_duck@team-df.org>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types=1);

namespace Ducks\Component\EncodingRepair\Traits;

use SplPriorityQueue;

/**
 * Common functionality for Chain of Responsibility pattern.
 *
 * @template T
 */
trait ChainOfResponsibilityTrait
{
    /**
     * @var null|SplPriorityQueue<int, T>
     */
    private ?SplPriorityQueue $queue = null;

    /**
     * @var list<array{handler: T, priority: int}>
     */
    private $registered = [];

    /**
     * Register a handler with optional priority override into the chain.
     *
     * @param T $handler Handler to register
     * @param int|null $_priority Priority override (null = use transcoder's default)
     *
     * @return void
     *
     * @psalm-suppress PossiblyUnusedParam
     */
    public function register($handler, ?int $_priority = null): void
    {
        $finalPriority = $priority ?? $handler->getPriority();

        $this->registered[] = [
            'handler' => $handler,
            'priority' => $finalPriority,
        ];

        $this->getSplPriorityQueue()->insert($handler, $finalPriority);
    }

    /**
     * Unregister a handler from the chain.
     *
     * @param T $handler Handler to remove
     *
     * @return void
     */
    private function unregister($handler): void
    {
        $this->registered = \array_values(
            \array_filter(
                $this->registered,
                static fn (array $item): bool => $item['handler'] !== $handler
            )
        );

        $this->queue = null;
    }

    /**
     * Rebuild queue from registered handlers.
     *
     * @return void
     */
    private function rebuildQueue(): void
    {
        /** @var SplPriorityQueue<int, T> $queue */
        $queue = new SplPriorityQueue();

        foreach ($this->registered as $item) {
            $queue->insert($item['handler'], $item['priority']);
        }

        $this->queue = $queue;
    }

    /**
     * Return the queue.
     *
     * @return SplPriorityQueue<int, T>
     */
    private function getSplPriorityQueue(): SplPriorityQueue
    {
        if (!$this->queue instanceof SplPriorityQueue) {
            /** @var SplPriorityQueue<int, T> $queue */
            $queue = new SplPriorityQueue();
            $this->queue = $queue;
        }

        return $this->queue;
    }
}
