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
