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

final class ObjCp1252 extends ObjEncoded
{
    public function getObjectEncoding(): string
    {
        return CharsetProcessorInterface::WINDOWS_1252;
    }
}
