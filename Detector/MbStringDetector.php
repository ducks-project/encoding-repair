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
 * MbString-based detector (requires ext-mbstring).
 *
 * @final
 */
final class MbStringDetector implements DetectorInterface
{
    private const DEFAULT_ENCODINGS = [
        'UTF-8',
        'CP1252',
        'ISO-8859-1',
        'ASCII',
    ];

    /**
     * @inheritDoc
     */
    public function detect(string $string, ?array $options = null): ?string
    {
        if (!$this->isAvailable()) {
            // @codeCoverageIgnoreStart
            return null;
            // @codeCoverageIgnoreEnd
        }

        /** @var mixed $encodings */
        $encodings = $options['encodings'] ?? self::DEFAULT_ENCODINGS;

        if (!\is_array($encodings)) {
            $encodings = self::DEFAULT_ENCODINGS;
        }

        $detected = \mb_detect_encoding($string, $encodings, true);

        return false !== $detected ? $detected : null;
    }

    /**
     * @inheritDoc
     */
    public function getPriority(): int
    {
        return 100;
    }

    /**
     * @inheritDoc
     */
    public function isAvailable(): bool
    {
        return \function_exists('mb_detect_encoding');
    }
}
