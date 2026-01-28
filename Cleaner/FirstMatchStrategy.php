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
 * Strategy that stops at first successful cleaner.
 *
 * @final
 *
 * @psalm-api
 */
final class FirstMatchStrategy implements CleanerStrategyInterface
{
    /**
     * {@inheritDoc}
     */
    public function execute(iterable $cleaners, string $data, string $encoding, array $options): ?string
    {
        foreach ($cleaners as $cleaner) {
            if (!$cleaner->isAvailable()) {
                // @codeCoverageIgnoreStart
                continue;
                // @codeCoverageIgnoreEnd
            }

            $result = $cleaner->clean($data, $encoding, $options);
            if (null !== $result) {
                return $result;
            }
        }

        return null;
    }
}
