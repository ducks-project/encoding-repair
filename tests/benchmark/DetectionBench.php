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

namespace Ducks\Component\EncodingRepair\Tests\benchmark;

use Ducks\Component\EncodingRepair\CharsetHelper;

/**
 * @Groups({"detection"})
 * @Revs(1000)
 * @Iterations(5)
 * @Warmup(2)
 */
final class DetectionBench
{
    private string $utf8String = 'Café résumé avec des accents éèêë';
    private string $mixedString = 'Simple ASCII text';

    public function __construct()
    {
    }

    /**
     * @Subject
     */
    public function benchDetectUtf8(): void
    {
        CharsetHelper::detect($this->utf8String);
    }

    /**
     * @Subject
     */
    public function benchDetectAscii(): void
    {
        CharsetHelper::detect($this->mixedString);
    }

    /**
     * @Subject
     */
    public function benchDetectWithCustomEncodings(): void
    {
        CharsetHelper::detect($this->utf8String, [
            'encodings' => ['UTF-8', 'ISO-8859-1', 'CP1252'],
        ]);
    }
}
