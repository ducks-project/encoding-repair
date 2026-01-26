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
use Ducks\Component\EncodingRepair\Tests\common\Phrase;
use stdClass;

/**
 * @Groups({"conversion"})
 *
 * @Revs(1000)
 *
 * @Iterations(5)
 *
 * @Warmup(2)
 */
final class ConversionBench
{
    /**
     * @var string
     */
    private string $utf8String;

    /**
     * @var array<string, string>
     */
    private array $smallArray;

    /**
     * @var array<string, string>
     */
    private array $largeArray;

    /**
     * @var object
     * @psalm-var \stdClass&object{name: string, password: string} $object
     */
    private object $object;

    public function __construct()
    {
        $obj = new ObjUtf8();

        $this->utf8String = Phrase::VALUE;

        $this->smallArray = $obj->__toArray();

        $this->largeArray = [];
        for ($i = 0; $i < 100; $i++) {
            $this->largeArray["field_{$i}"] = "{$this->utf8String} {$i}";
        }

        $this->object = (object) $this->smallArray;
    }

    /**
     * @Subject
     */
    public function benchSimpleStringToUtf8(): void
    {
        CharsetHelper::toUtf8($this->utf8String, CharsetHelper::WINDOWS_1252);
    }

    /**
     * @Subject
     */
    public function benchSimpleStringToIso(): void
    {
        CharsetHelper::toIso($this->utf8String, CharsetHelper::ENCODING_UTF8);
    }

    /**
     * @Subject
     */
    public function benchSmallArrayConversion(): void
    {
        CharsetHelper::toUtf8($this->smallArray, CharsetHelper::WINDOWS_1252);
    }

    /**
     * @Subject
     */
    public function benchLargeArrayConversion(): void
    {
        CharsetHelper::toUtf8($this->largeArray, CharsetHelper::WINDOWS_1252);
    }

    /**
     * @Subject
     */
    public function benchObjectConversion(): void
    {
        CharsetHelper::toUtf8($this->object, CharsetHelper::WINDOWS_1252);
    }

    /**
     * @Subject
     */
    public function benchAutoDetection(): void
    {
        CharsetHelper::toCharset($this->utf8String, CharsetHelper::ENCODING_UTF8, CharsetHelper::AUTO);
    }
}
