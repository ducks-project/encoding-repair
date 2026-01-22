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

namespace Ducks\Component\EncodingRepair\Detector;

use SplPriorityQueue;

/**
 * Chain of Responsibility for detectors with priority management.
 *
 * @final
 */
final class DetectorChain
{
    /**
     * @var null|SplPriorityQueue<int, DetectorInterface>
     */
    private ?SplPriorityQueue $queue;

    /**
     * @var list<array{detector: DetectorInterface, priority: int}>
     */
    private $registered = [];

    public function __construct()
    {
        $this->queue = null;
    }

    /**
     * Register a detector with optional priority override.
     *
     * @param DetectorInterface $detector Detector instance
     * @param int|null $priority Priority override (null = use detector's default)
     *
     * @return void
     */
    public function register(DetectorInterface $detector, ?int $priority = null): void
    {
        $finalPriority = $priority ?? $detector->getPriority();

        $this->registered[] = [
            'detector' => $detector,
            'priority' => $finalPriority,
        ];

        $this->getSplPriorityQueue()->insert($detector, $finalPriority);
    }

    /**
     * Execute detection using chain of responsibility.
     *
     * @param string $string String to analyze
     * @param array<string, mixed> $options Detection options
     *
     * @return string|null Detected encoding or null if all failed
     */
    public function detect(string $string, array $options): ?string
    {
        $this->rebuildQueue();

        foreach ($this->getSplPriorityQueue() as $detector) {
            if (!$detector->isAvailable()) {
                // @codeCoverageIgnoreStart
                continue;
                // @codeCoverageIgnoreEnd
            }

            $result = $detector->detect($string, $options);

            if (null !== $result) {
                return $result;
            }
        }

        // @codeCoverageIgnoreStart
        return null;
        // @codeCoverageIgnoreEnd
    }

    /**
     * Rebuild queue from registered detectors.
     *
     * @return void
     */
    private function rebuildQueue(): void
    {
        /** @var SplPriorityQueue<int, DetectorInterface> $queue */
        $queue = new SplPriorityQueue();

        foreach ($this->registered as $item) {
            $queue->insert($item['detector'], $item['priority']);
        }

        $this->queue = $queue;
    }

    /**
     * Return the queue.
     *
     * @return SplPriorityQueue<int, DetectorInterface>
     */
    private function getSplPriorityQueue(): SplPriorityQueue
    {
        if (!$this->queue instanceof SplPriorityQueue) {
            /** @var SplPriorityQueue<int, DetectorInterface> $queue */
            $queue = new SplPriorityQueue();
            $this->queue = $queue;
        }

        return $this->queue;
    }
}
