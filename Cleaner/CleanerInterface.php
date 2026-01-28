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

use Ducks\Component\EncodingRepair\PrioritizedHandlerInterface;

/**
 * Contract for string cleaning strategies.
 *
 * @psalm-api
 */
interface CleanerInterface extends PrioritizedHandlerInterface
{
    /**
     * Cleans invalid sequences from string.
     *
     * @param string $data String to clean
     * @param string $encoding Target encoding for validation
     * @param array<string, mixed> $options Cleaning options
     *
     * @return ?string Cleaned string or null if cleaner cannot handle
     */
    public function clean(string $data, string $encoding, array $options): ?string;

    /**
     * Check if cleaner is available on current system.
     *
     * @return bool True if available
     */
    public function isAvailable(): bool;
}
