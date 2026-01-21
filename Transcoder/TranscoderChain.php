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

namespace Ducks\Component\EncodingRepair\Transcoder;

use SplPriorityQueue;

/**
 * Chain of Responsibility for transcoders with priority management.
 *
 * @final
 */
final class TranscoderChain
{
    /**
     * @var null|SplPriorityQueue<int, TranscoderInterface>
     */
    private ?SplPriorityQueue $queue;

    /**
     * @var list<array{transcoder: TranscoderInterface, priority: int}>
     */
    private $registered = [];

    public function __construct()
    {
        $this->queue = null;
    }

    /**
     * Register a transcoder with optional priority override.
     *
     * @param TranscoderInterface $transcoder Transcoder instance
     * @param int|null $priority Priority override (null = use transcoder's default)
     *
     * @return void
     */
    public function register(TranscoderInterface $transcoder, ?int $priority = null): void
    {
        $finalPriority = $priority ?? $transcoder->getPriority();

        $this->registered[] = [
            'transcoder' => $transcoder,
            'priority' => $finalPriority,
        ];

        $this->getSplPriorityQueue()->insert($transcoder, $finalPriority);
    }

    /**
     * Execute transcoding using chain of responsibility.
     *
     * @param string $data Data to transcode
     * @param string $to Target encoding
     * @param string $from Source encoding
     * @param array<string, mixed> $options Conversion options
     *
     * @return string|null Transcoded string or null if all failed
     */
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

    /**
     * Rebuild queue from registered transcoders.
     *
     * @return void
     */
    private function rebuildQueue(): void
    {
        $this->queue = new SplPriorityQueue();

        foreach ($this->registered as $item) {
            $this->queue->insert($item['transcoder'], $item['priority']);
        }
    }

    /**
     * Return the queue.
     *
     * @return SplPriorityQueue<int, TranscoderInterface>
     */
    private function getSplPriorityQueue(): SplPriorityQueue
    {
        if (!$this->queue instanceof SplPriorityQueue) {
            $this->queue = new SplPriorityQueue();
        }

        return $this->queue;
    }
}
