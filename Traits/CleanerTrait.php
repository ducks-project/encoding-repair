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

/**
 * Trait providing common clean() implementation with performance optimization.
 */
trait CleanerTrait
{
    /**
     * Cleans invalid sequences from string.
     *
     * @param string $data Input string
     * @param string $encoding Target encoding
     * @param array<string, mixed> $options Cleaning options
     *
     * @return string|null Cleaned string or null if cleaner cannot handle
     */
    public function clean(string $data, string $encoding, array $options): ?string
    {
        if ('' === $data) {
            return $data;
        }

        return $this->doClean($data, $encoding, $options);
    }

    /**
     * Performs actual cleaning logic.
     *
     * @param string $data Input string (guaranteed non-empty)
     * @param string $encoding Target encoding
     * @param array<string, mixed> $options Cleaning options
     *
     * @return string|null Cleaned string or null if cleaner cannot handle
     */
    abstract protected function doClean(string $data, string $encoding, array $options): ?string;
}
