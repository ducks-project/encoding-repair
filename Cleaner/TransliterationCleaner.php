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
 * Cleaner that transliterates non-ASCII characters to ASCII.
 *
 * @psalm-api
 */
final class TransliterationCleaner implements CleanerInterface
{
    public function clean(string $data, string $encoding, array $options): ?string
    {
        $transliterated = @\iconv($encoding, 'ASCII//TRANSLIT', $data);

        return false !== $transliterated && $transliterated !== $data ? $transliterated : null;
    }

    public function getPriority(): int
    {
        return 30;
    }

    public function isAvailable(): bool
    {
        return \function_exists('iconv');
    }
}
