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

/**
 * Composite cleaner that groups multiple cleaners with a strategy.
 *
 * @final
 *
 * @psalm-api
 */
final class CompositeCleaner implements CleanerInterface
{
    /** @var array<CleanerInterface> */
    private array $cleaners;

    private CleanerStrategyInterface $strategy;

    private int $priority;

    /**
     * @param CleanerStrategyInterface|null $strategy Execution strategy (default: PipelineStrategy)
     * @param int $priority Priority for this composite (default: 100)
     * @param CleanerInterface ...$cleaners Cleaners to group
     */
    public function __construct(
        ?CleanerStrategyInterface $strategy = null,
        int $priority = 100,
        CleanerInterface ...$cleaners
    ) {
        $this->strategy = $strategy ?? new PipelineStrategy();
        $this->priority = $priority;
        $this->cleaners = $cleaners;
    }

    /**
     * {@inheritDoc}
     */
    public function clean(string $data, string $encoding, array $options): ?string
    {
        return $this->strategy->execute($this->cleaners, $data, $encoding, $options);
    }

    /**
     * {@inheritDoc}
     */
    public function getPriority(): int
    {
        return $this->priority;
    }

    /**
     * {@inheritDoc}
     */
    public function isAvailable(): bool
    {
        foreach ($this->cleaners as $cleaner) {
            if ($cleaner->isAvailable()) {
                return true;
            }
        }

        return false;
    }
}
