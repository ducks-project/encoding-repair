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

use Ducks\Component\EncodingRepair\Cleaner\BomCleaner;
use Ducks\Component\EncodingRepair\Cleaner\CleanerChain;
use Ducks\Component\EncodingRepair\Cleaner\HtmlEntityCleaner;
use Ducks\Component\EncodingRepair\Cleaner\IconvCleaner;
use Ducks\Component\EncodingRepair\Cleaner\MbScrubCleaner;
use Ducks\Component\EncodingRepair\Cleaner\NormalizerCleaner;
use Ducks\Component\EncodingRepair\Cleaner\PregMatchCleaner;
use Ducks\Component\EncodingRepair\Cleaner\TransliterationCleaner;
use Ducks\Component\EncodingRepair\Cleaner\Utf8FixerCleaner;
use Ducks\Component\EncodingRepair\Cleaner\WhitespaceCleaner;

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
    private string $withBom;
    private string $withEntities;
    private string $decomposed;
    private string $doubleEncoded;
    private string $withWhitespace;
    private string $withAccents;
    private MbScrubCleaner $mbscrub;
    private PregMatchCleaner $preg;
    private IconvCleaner $iconv;
    private BomCleaner $bom;
    private NormalizerCleaner $normalizer;
    private HtmlEntityCleaner $htmlEntity;
    private Utf8FixerCleaner $utf8Fixer;
    private WhitespaceCleaner $whitespace;
    private TransliterationCleaner $translit;
    private CleanerChain $chain;

    public function __construct()
    {
        $this->corruptedUtf8 = "Caf\xC3\xA9 \xC2\x88 invalid \x00 bytes";
        $this->validUtf8 = 'Café résumé avec des accents éèêë';
        $this->withBom = "\xEF\xBB\xBFCafé";
        $this->withEntities = 'Caf&eacute; &amp; R&eacute;sum&eacute;';
        $this->decomposed = "Cafe\u{0301}";
        $this->doubleEncoded = "CafÃ©";
        $this->withWhitespace = "Text  with   multiple    spaces";
        $this->withAccents = 'Café résumé';

        $this->mbscrub = new MbScrubCleaner();
        $this->preg = new PregMatchCleaner();
        $this->iconv = new IconvCleaner();
        $this->bom = new BomCleaner();
        $this->normalizer = new NormalizerCleaner();
        $this->htmlEntity = new HtmlEntityCleaner();
        $this->utf8Fixer = new Utf8FixerCleaner();
        $this->whitespace = new WhitespaceCleaner();
        $this->translit = new TransliterationCleaner();

        $this->chain = new CleanerChain();
        $this->chain->register($this->bom);
        $this->chain->register($this->mbscrub);
        $this->chain->register($this->normalizer);
        $this->chain->register($this->utf8Fixer);
        $this->chain->register($this->htmlEntity);
        $this->chain->register($this->preg);
        $this->chain->register($this->whitespace);
        $this->chain->register($this->translit);
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

    /**
     * @Subject
     */
    public function benchBomCleanBom(): void
    {
        $this->bom->clean($this->withBom, 'UTF-8', []);
    }

    /**
     * @Subject
     */
    public function benchBomCleanNoBom(): void
    {
        $this->bom->clean($this->validUtf8, 'UTF-8', []);
    }

    /**
     * @Subject
     */
    public function benchNormalizerCleanDecomposed(): void
    {
        $this->normalizer->clean($this->decomposed, 'UTF-8', []);
    }

    /**
     * @Subject
     */
    public function benchNormalizerCleanNormalized(): void
    {
        $this->normalizer->clean($this->validUtf8, 'UTF-8', []);
    }

    /**
     * @Subject
     */
    public function benchHtmlEntityCleanEntities(): void
    {
        $this->htmlEntity->clean($this->withEntities, 'UTF-8', []);
    }

    /**
     * @Subject
     */
    public function benchHtmlEntityCleanNoEntities(): void
    {
        $this->htmlEntity->clean($this->validUtf8, 'UTF-8', []);
    }

    /**
     * @Subject
     */
    public function benchUtf8FixerCleanDoubleEncoded(): void
    {
        $this->utf8Fixer->clean($this->doubleEncoded, 'UTF-8', []);
    }

    /**
     * @Subject
     */
    public function benchUtf8FixerCleanNoCorruption(): void
    {
        $this->utf8Fixer->clean($this->validUtf8, 'UTF-8', []);
    }

    /**
     * @Subject
     */
    public function benchWhitespaceCleanMessy(): void
    {
        $this->whitespace->clean($this->withWhitespace, 'UTF-8', []);
    }

    /**
     * @Subject
     */
    public function benchWhitespaceCleanNormal(): void
    {
        $this->whitespace->clean($this->validUtf8, 'UTF-8', []);
    }

    /**
     * @Subject
     */
    public function benchTranslitCleanAccents(): void
    {
        $this->translit->clean($this->withAccents, 'UTF-8', []);
    }

    /**
     * @Subject
     */
    public function benchTranslitCleanAscii(): void
    {
        $this->translit->clean('Hello World', 'UTF-8', []);
    }
}
