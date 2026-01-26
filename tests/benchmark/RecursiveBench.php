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
use Ducks\Component\EncodingRepair\Tests\common\ObjUtf8;
use Ducks\Component\EncodingRepair\Tests\common\Word;
use stdClass;

/**
 * @Groups({"recursive"})
 *
 * @Revs(100)
 *
 * @Iterations(5)
 *
 * @Warmup(2)
 */
final class RecursiveBench
{
    /**
     * @var array<string, mixed>
     */
    private array $shallowArray;

    /**
     * @var array<string, mixed>
     */
    private array $deepArray;

    /**
     * @var array<string, mixed>
     */
    private array $veryDeepArray;

    private object $shallowObject;
    private object $deepObject;

    /**
     * @var array<string, mixed>
     */
    private array $mixedStructure;

    public function __construct()
    {
        $obj = new ObjUtf8();

        $this->shallowArray = $obj->__toArray();

        $this->deepArray = [
            'level1' => [
                'level2' => [
                    'level3' => $obj->__toArray(),
                ],
            ],
        ];

        $this->veryDeepArray = [
            'l1' => [
                'l2' => [
                    'l3' => [
                        'l4' => [
                            'l5' => [
                                'l6' => $obj->__toArray(),
                            ],
                        ],
                    ],
                ],
            ],
        ];

        $this->shallowObject = (object) $obj->__toArray();

        $this->deepObject = new stdClass();
        $this->deepObject->level1 = new stdClass();
        $this->deepObject->level1->level2 = $obj->__toArray();

        $this->mixedStructure = [
            'data' => [
                'user' => (object) $obj->__toArray(),
                'items' => Word::getGoodUtf8Word(),
            ],
        ];
    }

    /**
     * @Subject
     */
    public function benchShallowArray(): void
    {
        CharsetHelper::toUtf8($this->shallowArray, CharsetHelper::WINDOWS_1252);
    }

    /**
     * @Subject
     */
    public function benchDeepArray(): void
    {
        CharsetHelper::toUtf8($this->deepArray, CharsetHelper::WINDOWS_1252);
    }

    /**
     * @Subject
     */
    public function benchVeryDeepArray(): void
    {
        CharsetHelper::toUtf8($this->veryDeepArray, CharsetHelper::WINDOWS_1252);
    }

    /**
     * @Subject
     */
    public function benchShallowObject(): void
    {
        CharsetHelper::toUtf8($this->shallowObject, CharsetHelper::WINDOWS_1252);
    }

    /**
     * @Subject
     */
    public function benchDeepObject(): void
    {
        CharsetHelper::toUtf8($this->deepObject, CharsetHelper::WINDOWS_1252);
    }

    /**
     * @Subject
     */
    public function benchMixedStructure(): void
    {
        CharsetHelper::toUtf8($this->mixedStructure, CharsetHelper::WINDOWS_1252);
    }
}
