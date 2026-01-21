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
 * MbString-based transcoder (requires ext-mbstring).
 *
 * @final
 */
final class MbStringTranscoder implements TranscoderInterface
{
    /**
     * @inheritDoc
     */
    public function transcode(string $data, string $to, string $from, ?array $options = null): ?string
    {
        if (!$this->isAvailable()) {
            return null;
        }

        // Use silence operator (@) instead of set_error_handler
        // set_error_handler is too expensive for high-volume loops.
        /** @var false|string $result */
        $result = @\mb_convert_encoding($data, $to, $from);

        return false !== $result ? $result : null;
    }

    /**
     * @inheritDoc
     */
    public function getPriority(): int
    {
        return 10;
    }

    /**
     * @inheritDoc
     */
    public function isAvailable(): bool
    {
        return \function_exists('mb_convert_encoding');
    }
}
