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
use Ducks\Component\EncodingRepair\Tests\common\ObjBadUtf8;
use Ducks\Component\EncodingRepair\Tests\common\Phrase;

/**
 * @Groups({"repair"})
 *
 * @Revs(1000)
 *
 * @Iterations(5)
 *
 * @Warmup(2)
 */
final class RepairBench
{
    private string $doubleEncoded = "Caf\xC3\x83\xC2\xA9"; // Double-encoded Café
    private string $validUtf8;
    private array $doubleEncodedArray;

    public function __construct()
    {
        $obj = new ObjBadUtf8();

        $this->validUtf8 = Phrase::getValue();
        $this->doubleEncodedArray = $obj->__toArray();
    }

    /**
     * @Subject
     */
    public function benchRepairDoubleEncoded(): void
    {
        CharsetHelper::repair($this->doubleEncoded);
    }

    /**
     * @Subject
     */
    public function benchRepairValidUtf8(): void
    {
        CharsetHelper::repair($this->validUtf8);
    }

    /**
     * @Subject
     */
    public function benchRepairArray(): void
    {
        CharsetHelper::repair($this->doubleEncodedArray);
    }

    /**
     * @Subject
     */
    public function benchRepairWithMaxDepth(): void
    {
        CharsetHelper::repair(
            $this->doubleEncoded,
            CharsetHelper::ENCODING_UTF8,
            CharsetHelper::ENCODING_ISO,
            [
                'maxDepth' => 10,
            ]
        );
    }
}
