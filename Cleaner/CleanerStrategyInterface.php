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
 * Contract for cleaner execution strategies.
 *
 * @psalm-api
 */
interface CleanerStrategyInterface
{
    /**
     * Execute cleaners according to strategy.
     *
     * @param iterable<CleanerInterface> $cleaners Available cleaners
     * @param string $data String to clean
     * @param string $encoding Target encoding
     * @param array<string, mixed> $options Cleaning options
     *
     * @return ?string Cleaned string or null if no cleaner succeeded
     */
    public function execute(iterable $cleaners, string $data, string $encoding, array $options): ?string;
}
