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

use Ducks\Component\EncodingRepair\Cleaner\CleanerChain;
use Ducks\Component\EncodingRepair\Cleaner\IconvCleaner;
use Ducks\Component\EncodingRepair\Cleaner\MbScrubCleaner;
use Ducks\Component\EncodingRepair\Cleaner\PregMatchCleaner;

/**
 * @Groups({"cleaner"})
 *
 * @Revs(1000)
 *
 * @Iterations(5)
 *
 * @Warmup(2)
 */
final class CleanerBench
{
    private string $corruptedUtf8;
    private string $validUtf8;
    private MbScrubCleaner $mbscrub;
    private PregMatchCleaner $preg;
    private IconvCleaner $iconv;
    private CleanerChain $chain;

    public function __construct()
    {
        // Corrupted UTF-8 with invalid sequences
        $this->corruptedUtf8 = "Caf\xC3\xA9 \xC2\x88 invalid \x00 bytes";
        $this->validUtf8 = 'Café résumé avec des accents éèêë';

        $this->mbscrub = new MbScrubCleaner();
        $this->preg = new PregMatchCleaner();
        $this->iconv = new IconvCleaner();

        $this->chain = new CleanerChain();
        $this->chain->register($this->mbscrub);
        $this->chain->register($this->preg);
        $this->chain->register($this->iconv);
    }

    /**
     * @Subject
     */
    public function benchMbScrubCleanCorrupted(): void
    {
        $this->mbscrub->clean($this->corruptedUtf8, 'UTF-8', []);
    }

    /**
     * @Subject
     */
    public function benchPregMatchCleanCorrupted(): void
    {
        $this->preg->clean($this->corruptedUtf8, 'UTF-8', []);
    }

    /**
     * @Subject
     */
    public function benchIconvCleanCorrupted(): void
    {
        $this->iconv->clean($this->corruptedUtf8, 'UTF-8', []);
    }

    /**
     * @Subject
     */
    public function benchCleanerChainCorrupted(): void
    {
        $this->chain->clean($this->corruptedUtf8, 'UTF-8', []);
    }

    /**
     * @Subject
     */
    public function benchMbScrubCleanValid(): void
    {
        $this->mbscrub->clean($this->validUtf8, 'UTF-8', []);
    }

    /**
     * @Subject
     */
    public function benchPregMatchCleanValid(): void
    {
        $this->preg->clean($this->validUtf8, 'UTF-8', []);
    }

    /**
     * @Subject
     */
    public function benchIconvCleanValid(): void
    {
        $this->iconv->clean($this->validUtf8, 'UTF-8', []);
    }

    /**
     * @Subject
     */
    public function benchCleanerChainValid(): void
    {
        $this->chain->clean($this->validUtf8, 'UTF-8', []);
    }
}
