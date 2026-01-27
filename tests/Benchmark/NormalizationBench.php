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

namespace Ducks\Component\EncodingRepair\Tests\Benchmark;

use Ducks\Component\EncodingRepair\CharsetHelper;
use Ducks\Component\EncodingRepair\Tests\Common\Phrase;

/**
 * @Groups({"normalization"})
 *
 * @Revs(1000)
 *
 * @Iterations(5)
 *
 * @Warmup(2)
 */
final class NormalizationBench
{
    private string $shortString;
    private string $longString;

    /**
     * @var array<string, string>
     */
    private array $dataArray;

    public function __construct()
    {
        $this->shortString = Phrase::getValue();
        $this->longString = \str_repeat($this->shortString . ' ', 100);
        $this->dataArray = \array_fill(0, 50, $this->shortString);
    }

    /**
     * @Subject
     */
    public function benchShortStringWithNormalization(): void
    {
        CharsetHelper::toUtf8($this->shortString, CharsetHelper::WINDOWS_1252, ['normalize' => true]);
    }

    /**
     * @Subject
     */
    public function benchShortStringWithoutNormalization(): void
    {
        CharsetHelper::toUtf8($this->shortString, CharsetHelper::WINDOWS_1252, ['normalize' => false]);
    }

    /**
     * @Subject
     */
    public function benchLongStringWithNormalization(): void
    {
        CharsetHelper::toUtf8($this->longString, CharsetHelper::WINDOWS_1252, ['normalize' => true]);
    }

    /**
     * @Subject
     */
    public function benchLongStringWithoutNormalization(): void
    {
        CharsetHelper::toUtf8($this->longString, CharsetHelper::WINDOWS_1252, ['normalize' => false]);
    }

    /**
     * @Subject
     */
    public function benchArrayWithNormalization(): void
    {
        CharsetHelper::toUtf8($this->dataArray, CharsetHelper::WINDOWS_1252, ['normalize' => true]);
    }

    /**
     * @Subject
     */
    public function benchArrayWithoutNormalization(): void
    {
        CharsetHelper::toUtf8($this->dataArray, CharsetHelper::WINDOWS_1252, ['normalize' => false]);
    }
}
