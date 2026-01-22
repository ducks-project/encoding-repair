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

use finfo;

/**
 * FileInfo-based detector (requires ext-fileinfo).
 *
 * @final
 */
final class FileInfoDetector implements DetectorInterface
{
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

        $args = [];

        /** @var mixed $magic */
        $magic = $options['finfo_magic'] ?? null;
        if (\is_string($magic)) {
            // @codeCoverageIgnoreStart
            $args[] = $magic;
            // @codeCoverageIgnoreEnd
        }

        $finfo = new finfo(FILEINFO_MIME_ENCODING, ...$args);

        $detected = $finfo->buffer($string, ...$this->resolveOptions($options ?? []));

        if (false === $detected || 'binary' === $detected) {
            return null;
        }

        return \strtoupper($detected);
    }

    /**
     * @inheritDoc
     */
    public function getPriority(): int
    {
        return 50;
    }

    /**
     * @inheritDoc
     */
    public function isAvailable(): bool
    {
        return \class_exists(finfo::class);
    }

    /**
     * Resolve options for finfo::buffer().
     *
     * @param array<string, mixed> $options Detection options
     *
     * @return list{0: int, 1?: resource} An array where [0] is an int and [1] is an optionnal resource
     */
    private function resolveOptions(array $options): array
    {
        $args = [];

        /** @var mixed $flags */
        $flags = $options['finfo_flags'] ?? \FILEINFO_NONE;
        if (!\is_int($flags)) {
            $flags = \FILEINFO_NONE;
        }
        $args[] = $flags;

        /** @var mixed $context */
        $context = $options['finfo_context'] ?? null;
        if (\is_resource($context)) {
            // @codeCoverageIgnoreStart
            $args[] = $context;
            // @codeCoverageIgnoreEnd
        }

        return $args;
    }
}
