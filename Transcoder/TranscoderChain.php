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

use Ducks\Component\EncodingRepair\Traits\ChainOfResponsibilityTrait;

/**
 * Chain of Responsibility for transcoders with priority management.
 *
 * @final
 */
final class TranscoderChain
{
    /**
     * @use ChainOfResponsibilityTrait<TranscoderInterface>
     */
    use ChainOfResponsibilityTrait {
        ChainOfResponsibilityTrait::register as chainRegister;
        ChainOfResponsibilityTrait::unregister as chainUnregister;
    }

    /**
     * Register a transcoder with optional priority override.
     *
     * @param TranscoderInterface $transcoder Transcoder instance
     * @param int|null $priority Priority override (null = use transcoder's default)
     *
     * @return void
     */
    public function register(TranscoderInterface $transcoder, ?int $priority = null): void
    {
        $this->chainRegister($transcoder, $priority);
    }

    /**
     * Unregister a transcoder from the chain.
     *
     * @param TranscoderInterface $transcoder Transcoder instance to remove
     *
     * @return void
     */
    public function unregister(TranscoderInterface $transcoder): void
    {
        $this->chainUnregister($transcoder);
    }

    /**
     * Execute transcoding using chain of responsibility.
     *
     * @param string $data Data to transcode
     * @param string $to Target encoding
     * @param string $from Source encoding
     * @param array<string, mixed> $options Conversion options
     *
     * @return string|null Transcoded string or null if all failed
     */
    public function transcode(string $data, string $to, string $from, array $options): ?string
    {
        $this->rebuildQueue();

        foreach ($this->getSplPriorityQueue() as $transcoder) {
            if (!$transcoder->isAvailable()) {
                // @codeCoverageIgnoreStart
                continue;
                // @codeCoverageIgnoreEnd
            }

            $result = $transcoder->transcode($data, $to, $from, $options);

            if (null !== $result) {
                return $result;
            }
        }

        // @codeCoverageIgnoreStart
        return null;
        // @codeCoverageIgnoreEnd
    }
}
