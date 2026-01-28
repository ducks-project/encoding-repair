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
 * Strategy that applies all cleaners successively (middleware pattern).
 *
 * @final
 *
 * @psalm-api
 */
final class PipelineStrategy implements CleanerStrategyInterface
{
    /**
     * {@inheritDoc}
     */
    public function execute(iterable $cleaners, string $data, string $encoding, array $options): ?string
    {
        $result = $data;
        $modified = false;

        foreach ($cleaners as $cleaner) {
            if (!$cleaner->isAvailable()) {
                // @codeCoverageIgnoreStart
                continue;
                // @codeCoverageIgnoreEnd
            }

            $cleaned = $cleaner->clean($result, $encoding, $options);
            if (null !== $cleaned) {
                $result = $cleaned;
                $modified = true;
            }
        }

        return $modified ? $result : null;
    }
}
