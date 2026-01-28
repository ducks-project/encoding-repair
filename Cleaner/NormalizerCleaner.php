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

use Normalizer;

/**
 * Cleaner using Normalizer to normalize Unicode characters (NFC).
 *
 * @psalm-api
 */
final class NormalizerCleaner implements CleanerInterface
{
    public function clean(string $data, string $encoding, array $options): ?string
    {
        if ('UTF-8' !== \strtoupper($encoding)) {
            return null;
        }

        if (!\class_exists(Normalizer::class)) {
            return null;
        }

        $normalized = Normalizer::normalize($data, Normalizer::NFC);

        return false !== $normalized ? $normalized : null;
    }

    public function getPriority(): int
    {
        return 90;
    }

    public function isAvailable(): bool
    {
        return \class_exists(Normalizer::class);
    }
}
