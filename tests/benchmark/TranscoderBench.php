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
 * @Groups({"transcoder"})
 *
 * @Revs(1000)
 *
 * @Iterations(5)
 *
 * @Warmup(2)
 */
final class TranscoderBench
{
    private string $utf8String = 'Café résumé avec des accents éèêë';
    private IconvTranscoder $iconv;
    private MbStringTranscoder $mbstring;
    private UConverterTranscoder $uconverter;
    private TranscoderChain $chain;

    public function __construct()
    {
        $this->iconv = new IconvTranscoder();
        $this->mbstring = new MbStringTranscoder();
        $this->uconverter = new UConverterTranscoder();
        $this->chain = new TranscoderChain();
        $this->chain->register($this->uconverter);
        $this->chain->register($this->iconv);
        $this->chain->register($this->mbstring);
    }

    /**
     * @Subject
     */
    public function benchIconvTranscode(): void
    {
        $this->iconv->transcode($this->utf8String, 'ISO-8859-1', 'UTF-8', []);
    }

    /**
     * @Subject
     */
    public function benchMbStringTranscode(): void
    {
        $this->mbstring->transcode($this->utf8String, 'ISO-8859-1', 'UTF-8', []);
    }

    /**
     * @Subject
     */
    public function benchUConverterTranscode(): void
    {
        $this->uconverter->transcode($this->utf8String, 'ISO-8859-1', 'UTF-8', []);
    }

    /**
     * @Subject
     */
    public function benchTranscoderChain(): void
    {
        $this->chain->transcode($this->utf8String, 'ISO-8859-1', 'UTF-8', []);
    }
}
