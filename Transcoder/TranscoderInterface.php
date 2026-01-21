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

/**
 * Interface for charset transcoder implementations.
 *
 * @psalm-api
 */
interface TranscoderInterface
{
    /**
     * Transcode data from one encoding to another.
     *
     * @param string $data Data to transcode
     * @param string $to Target encoding
     * @param string $from Source encoding
     * @param null|array<string, mixed> $options Conversion options
     *
     * @return string|null Transcoded string or null if transcoder cannot handle
     */
    public function transcode(string $data, string $to, string $from, ?array $options = null): ?string;

    /**
     * Get transcoder priority (higher = executed first).
     *
     * @return int Priority value
     */
    public function getPriority(): int;

    /**
     * Check if transcoder is available on current system.
     *
     * @return bool True if available
     */
    public function isAvailable(): bool;
}
