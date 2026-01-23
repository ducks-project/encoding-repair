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

use Ducks\Component\EncodingRepair\CharsetProcessor;

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
    private string $utf8String = 'Café résumé avec des accents éèêë';
    private array $testArray;

    public function __construct()
    {
        $this->processor = new CharsetProcessor();
        $this->testArray = [
            'name' => 'José García',
            'city' => 'São Paulo',
            'nested' => ['field' => 'Café résumé'],
        ];
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
