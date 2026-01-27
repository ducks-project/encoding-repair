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

namespace Ducks\Component\EncodingRepair\Tests\Common;

use Ducks\Component\EncodingRepair\CharsetProcessorInterface;

/**
 * @property-read string $value
 */
final class Phrase
{
    use ReadOnlyPropertiesTrait;

    public const VALUE = 'Café résumé avec des accents éèêë';

    private string $value;

    public function __construct()
    {
        $this->value = self::VALUE;
    }

    public function getCp1252(): string
    {
        return \mb_convert_encoding(
            $this->value,
            CharsetProcessorInterface::WINDOWS_1252,
            CharsetProcessorInterface::ENCODING_UTF8
        ) ?: $this->value;
    }

    public function getIso(): string
    {
        return \mb_convert_encoding(
            $this->value,
            CharsetProcessorInterface::ENCODING_ISO,
            CharsetProcessorInterface::ENCODING_UTF8
        ) ?: $this->value;
    }

    public function getAscii(): string
    {
        return 'Cafe resume avec des accents eeee';
    }

    public function __toString(): string
    {
        return $this->value;
    }

    public static function getValue(): string
    {
        $instance = new self();

        return (string) $instance;
    }
}
