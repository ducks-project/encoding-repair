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

namespace Ducks\Component\EncodingRepair\Tests\common;

use Ducks\Component\EncodingRepair\CharsetProcessorInterface;

final class ObjCp1252 extends Obj
{
    use ObjTrait;
    use ReadOnlyPropertiesTrait;

    public function __construct()
    {
        // phpcs:disable Generic.Files.LineLength
        parent::__construct(
            \mb_convert_encoding('José García', CharsetProcessorInterface::WINDOWS_1252, CharsetProcessorInterface::ENCODING_UTF8),
            \mb_convert_encoding('josé@example.com', CharsetProcessorInterface::WINDOWS_1252, CharsetProcessorInterface::ENCODING_UTF8),
            \mb_convert_encoding('Brésil', CharsetProcessorInterface::WINDOWS_1252, CharsetProcessorInterface::ENCODING_UTF8),
            \mb_convert_encoding('São Paulo', CharsetProcessorInterface::WINDOWS_1252, CharsetProcessorInterface::ENCODING_UTF8),
            \mb_convert_encoding('password', CharsetProcessorInterface::WINDOWS_1252, CharsetProcessorInterface::ENCODING_UTF8)
        );
        // phpcs:enable Generic.Files.LineLength
    }
}
