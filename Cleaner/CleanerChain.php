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
 * Chain of Responsibility for string cleaners.
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

    /**
     * Register a cleaner with optional priority override.
     *
     * @param CleanerInterface $cleaner Cleaner instance
     * @param int|null $priority Priority override (null = use cleaner's default)
     *
     * @return void
     */
    public function register(CleanerInterface $cleaner, ?int $priority = null): void
    {
        $this->chainRegister($cleaner, $priority);
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
     * Cleans string using registered cleaners.
     *
     * @param string $data String to clean
     * @param string $encoding Target encoding
     * @param array<string, mixed> $options Cleaning options
     *
     * @return ?string Cleaned string or null if no cleaner succeeded
     */
    public function clean(string $data, string $encoding, array $options): ?string
    {
        // Clone the queue to avoid consuming it
        $queue = clone $this->getSplPriorityQueue();

        foreach ($queue as $cleaner) {
            if (!$cleaner->isAvailable()) {
                // @codeCoverageIgnoreStart
                continue;
                // @codeCoverageIgnoreEnd
            }

            $result = $cleaner->clean($data, $encoding, $options);
            if (null !== $result) {
                return $result;
            }
        }

        return null;
    }
}
