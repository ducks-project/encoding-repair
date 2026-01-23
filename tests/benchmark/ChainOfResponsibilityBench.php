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

use Ducks\Component\EncodingRepair\Transcoder\IconvTranscoder;
use Ducks\Component\EncodingRepair\Transcoder\MbStringTranscoder;
use Ducks\Component\EncodingRepair\Transcoder\TranscoderChain;
use Ducks\Component\EncodingRepair\Transcoder\UConverterTranscoder;

/**
 * @Groups({"chain"})
 *
 * @Revs(1000)
 *
 * @Iterations(5)
 *
 * @Warmup(2)
 */
final class ChainOfResponsibilityBench
{
    private IconvTranscoder $iconv;
    private MbStringTranscoder $mbstring;
    private UConverterTranscoder $uconverter;
    private string $testString = 'Café résumé';

    public function __construct()
    {
        $this->iconv = new IconvTranscoder();
        $this->mbstring = new MbStringTranscoder();
        $this->uconverter = new UConverterTranscoder();
    }

    /**
     * @Subject
     */
    public function benchRegisterSingleHandler(): void
    {
        $chain = new TranscoderChain();
        $chain->register($this->iconv);
    }

    /**
     * @Subject
     */
    public function benchRegisterMultipleHandlers(): void
    {
        $chain = new TranscoderChain();
        $chain->register($this->uconverter);
        $chain->register($this->iconv);
        $chain->register($this->mbstring);
    }

    /**
     * @Subject
     */
    public function benchUnregisterHandler(): void
    {
        $chain = new TranscoderChain();
        $chain->register($this->iconv);
        $chain->register($this->mbstring);
        $chain->unregister($this->iconv);
    }

    /**
     * @Subject
     */
    public function benchChainTraversal(): void
    {
        $chain = new TranscoderChain();
        $chain->register($this->uconverter);
        $chain->register($this->iconv);
        $chain->register($this->mbstring);
        $chain->transcode($this->testString, 'ISO-8859-1', 'UTF-8', []);
    }
}
