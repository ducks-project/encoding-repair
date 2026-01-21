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

use UConverter;

/**
 * UConverter-based transcoder (requires ext-intl).
 *
 * @final
 */
final class UConverterTranscoder implements TranscoderInterface
{
    /**
     * @inheritDoc
     */
    public function transcode(string $data, string $to, string $from, ?array $options = null): ?string
    {
        if (!$this->isAvailable()) {
            return null;
        }

        /** @var false|string $result */
        // @phpstan-ignore argument.type
        $result = UConverter::transcode($data, $to, $from, $this->resolveOptions($options ?? []));

        return false !== $result ? $result : null;
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
        return \class_exists(UConverter::class);
    }

    /**
     * Resolve options for UConverter.
     *
     * @param array<string,mixed> $options
     *
     * @return array<string,mixed>|null
     */
    private function resolveOptions(array $options): ?array
    {
        return \array_intersect_key(
            $options,
            ['to_subst' => true]
        ) ?: null;
    }
}
