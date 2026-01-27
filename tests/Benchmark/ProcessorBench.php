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

use Ducks\Component\EncodingRepair\CharsetProcessor;
use Ducks\Component\EncodingRepair\Tests\Common\ObjUtf8;
use Ducks\Component\EncodingRepair\Tests\Common\Phrase;

/**
 * @Groups({"processor"})
 *
 * @Revs(1000)
 *
 * @Iterations(5)
 *
 * @Warmup(2)
 */
final class ProcessorBench
{
    private CharsetProcessor $processor;
    private string $utf8String;
    private array $testArray;

    public function __construct()
    {
        $this->processor = new CharsetProcessor();

        $this->utf8String = Phrase::getValue();

        $obj = new ObjUtf8();
        $this->testArray = $obj->__toArray();
        $this->testArray['nested'] = ['field' => $this->utf8String];
    }

    /**
     * @Subject
     */
    public function benchProcessorDetect(): void
    {
        $this->processor->detect($this->utf8String);
    }

    /**
     * @Subject
     */
    public function benchProcessorToUtf8(): void
    {
        $this->processor->toUtf8($this->utf8String);
    }

    /**
     * @Subject
     */
    public function benchProcessorToIso(): void
    {
        $this->processor->toIso($this->utf8String);
    }

    /**
     * @Subject
     */
    public function benchProcessorRepair(): void
    {
        $this->processor->repair($this->utf8String);
    }

    /**
     * @Subject
     */
    public function benchProcessorArrayConversion(): void
    {
        $this->processor->toUtf8($this->testArray);
    }
}
