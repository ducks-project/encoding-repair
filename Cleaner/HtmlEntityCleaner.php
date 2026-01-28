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
 * Cleaner that decodes HTML entities.
 *
 * @psalm-api
 */
final class HtmlEntityCleaner implements CleanerInterface
{
    public function clean(string $data, string $encoding, array $options): ?string
    {
        // Only decode if there are HTML entities
        if (false === \strpos($data, '&')) {
            return null;
        }

        $decoded = \html_entity_decode($data, \ENT_QUOTES | \ENT_HTML5, $encoding);

        return $decoded !== $data ? $decoded : null;
    }

    public function getPriority(): int
    {
        return 60;
    }

    public function isAvailable(): bool
    {
        return true;
    }
}
