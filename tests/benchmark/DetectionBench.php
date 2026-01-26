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
use Ducks\Component\EncodingRepair\Tests\common\Phrase;

/**
 * @Groups({"detection"})
 *
 * @Revs(1000)
 *
 * @Iterations(5)
 *
 * @Warmup(2)
 */
final class DetectionBench
{
    private string $utf8String;
    private string $mixedString;

    public function __construct()
    {
        $phrase = new Phrase();

        $this->utf8String = (string) $phrase;
        $this->mixedString = $phrase->getAscii();
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
