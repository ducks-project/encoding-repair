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

namespace Ducks\Component\EncodingRepair\Cleaner;

use Ducks\Component\EncodingRepair\Traits\ChainOfResponsibilityTrait;

/**
 * Pipeline for string cleaners with configurable execution strategy.
 *
 * @final
 *
 * @psalm-api
 */
final class CleanerChain
{
    /**
     * @use ChainOfResponsibilityTrait<CleanerInterface>
     */
    use ChainOfResponsibilityTrait {
        ChainOfResponsibilityTrait::register as chainRegister;
        ChainOfResponsibilityTrait::unregister as chainUnregister;
    }

    private CleanerStrategyInterface $strategy;

    /** @var array<string, array<string>> */
    private array $cleanerTags = [];

    public function __construct(?CleanerStrategyInterface $strategy = null)
    {
        $this->strategy = $strategy ?? new PipelineStrategy();
    }

    /**
     * Register a cleaner with optional priority and tags.
     *
     * @param CleanerInterface $cleaner Cleaner instance
     * @param int|null $priority Priority override (null = use cleaner's default)
     * @param array<string> $tags Tags for selective execution
     *
     * @return void
     */
    public function register(CleanerInterface $cleaner, ?int $priority = null, array $tags = []): void
    {
        $this->chainRegister($cleaner, $priority);

        if (!empty($tags)) {
            $this->cleanerTags[\spl_object_hash($cleaner)] = $tags;

            if ($this->strategy instanceof TaggedStrategy) {
                $this->strategy->registerTags($cleaner, $tags);
            }
        }
    }

    /**
     * Unregister a cleaner from the chain.
     *
     * @param CleanerInterface $cleaner Cleaner instance to remove
     *
     * @return void
     */
    public function unregister(CleanerInterface $cleaner): void
    {
        $this->chainUnregister($cleaner);
    }

    /**
     * Set execution strategy.
     *
     * @param CleanerStrategyInterface $strategy Execution strategy
     *
     * @return void
     */
    public function setStrategy(CleanerStrategyInterface $strategy): void
    {
        $this->strategy = $strategy;

        // Re-register tags if using TaggedStrategy
        if ($strategy instanceof TaggedStrategy) {
            $queue = clone $this->getSplPriorityQueue();
            foreach ($queue as $cleaner) {
                $hash = \spl_object_hash($cleaner);
                if (isset($this->cleanerTags[$hash])) {
                    $strategy->registerTags($cleaner, $this->cleanerTags[$hash]);
                }
            }
        }
    }

    /**
     * Cleans string using registered cleaners and current strategy.
     *
     * @param string $data String to clean
     * @param string $encoding Target encoding
     * @param array<string, mixed> $options Cleaning options
     *
     * @return ?string Cleaned string or null if no cleaner succeeded
     */
    public function clean(string $data, string $encoding, array $options): ?string
    {
        $queue = clone $this->getSplPriorityQueue();

        return $this->strategy->execute($queue, $data, $encoding, $options);
    }
}
