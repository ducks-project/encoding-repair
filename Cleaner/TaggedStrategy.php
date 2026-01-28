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
 * Strategy that applies only cleaners matching specified tags.
 *
 * @final
 *
 * @psalm-api
 */
final class TaggedStrategy implements CleanerStrategyInterface
{
    /** @var array<string> */
    private array $tags;

    /** @var array<string, array<string>> */
    private array $cleanerTags = [];

    /**
     * @param array<string> $tags Tags to match
     */
    public function __construct(array $tags)
    {
        $this->tags = $tags;
    }

    /**
     * Register tags for a cleaner.
     *
     * @param CleanerInterface $cleaner Cleaner instance
     * @param array<string> $tags Tags for this cleaner
     *
     * @return void
     */
    public function registerTags(CleanerInterface $cleaner, array $tags): void
    {
        $this->cleanerTags[\spl_object_hash($cleaner)] = $tags;
    }

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

            $hash = \spl_object_hash($cleaner);
            $cleanerTags = $this->cleanerTags[$hash] ?? [];

            // Skip if no matching tags
            if (empty(\array_intersect($this->tags, $cleanerTags))) {
                continue;
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
