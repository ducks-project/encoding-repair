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

/**
 * Interface for charset detector implementations.
 *
 * @psalm-api
 */
interface DetectorInterface
{
    /**
     * Detect charset encoding of a string.
     *
     * @param string $string String to analyze
     * @param null|array<string, mixed> $options Detection options
     *
     * @return string|null Detected encoding or null if cannot detect
     */
    public function detect(string $string, ?array $options = null): ?string;

    /**
     * Get detector priority (higher = executed first).
     *
     * @return int Priority value
     */
    public function getPriority(): int;

    /**
     * Check if detector is available on current system.
     *
     * @return bool True if available
     */
    public function isAvailable(): bool;
}
