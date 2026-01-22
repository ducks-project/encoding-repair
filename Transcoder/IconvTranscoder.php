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

/**
 * Iconv-based transcoder (requires ext-iconv).
 *
 * @final
 */
final class IconvTranscoder implements TranscoderInterface
{
    /**
     * @inheritDoc
     */
    public function transcode(string $data, string $to, string $from, ?array $options = null): ?string
    {
        if (!$this->isAvailable()) {
            // @codeCoverageIgnoreStart
            return null;
            // @codeCoverageIgnoreEnd
        }

        $suffix = $this->buildSuffix($options ?? []);

        // Use silence operator (@) instead of
        // \set_error_handler(static fn (): bool => true);
        // set_error_handler is too expensive for high-volume loops.
        $result = @\iconv($from, $to . $suffix, $data);

        return false !== $result ? $result : null;
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
        return \function_exists('iconv');
    }

    /**
     * Build iconv suffix from options.
     *
     * @param array<string, mixed> $options Conversion options
     *
     * @return string Suffix string
     */
    private function buildSuffix(array $options): string
    {
        $suffix = '';

        if (true === ($options['translit'] ?? true)) {
            $suffix .= '//TRANSLIT';
        }

        if (true === ($options['ignore'] ?? true)) {
            $suffix .= '//IGNORE';
        }

        return $suffix;
    }
}
