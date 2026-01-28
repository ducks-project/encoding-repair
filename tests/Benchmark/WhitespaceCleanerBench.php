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

use PhpBench\Attributes\BeforeMethods;
use PhpBench\Attributes\Iterations;
use PhpBench\Attributes\Revs;

/**
 * Benchmark for whitespace cleaning strategies.
 *
 * @Groups({"whitespace"})
 *
 * @Revs(1000)
 *
 * @Iterations(5)
 *
 * @Warmup(2)
 */
final class WhitespaceCleanerBench
{
    private string $shortText;
    private string $mediumText;
    private string $longText;

    public function __construct()
    {
        $this->shortText = "Hello  world\t\tfoo   bar";
        $this->mediumText = \str_repeat("Multiple   spaces\t\there  and\xC2\xA0there\n\n", 10);
        $this->longText = \str_repeat("Text  with    multiple\t\t\tspaces   everywhere\xC2\xA0\xC2\xA0\n", 100);
    }

    /**
     * @Subject
     */
    public function benchPregReplaceShort(): void
    {
        \preg_replace('/[\s\xC2\xA0]+/u', ' ', $this->shortText);
    }

    /**
     * @Subject
     */
    public function benchPregReplaceMedium(): void
    {
        \preg_replace('/[\s\xC2\xA0]+/u', ' ', $this->mediumText);
    }

    /**
     * @Subject
     */
    public function benchPregReplaceLong(): void
    {
        \preg_replace('/[\s\xC2\xA0]+/u', ' ', $this->longText);
    }

    /**
     * @Subject
     */
    public function benchPregReplaceSimpleShort(): void
    {
        \preg_replace('/\s+/', ' ', $this->shortText);
    }

    /**
     * @Subject
     */
    public function benchPregReplaceSimpleMedium(): void
    {
        \preg_replace('/\s+/', ' ', $this->mediumText);
    }

    /**
     * @Subject
     */
    public function benchPregReplaceSimpleLong(): void
    {
        \preg_replace('/\s+/', ' ', $this->longText);
    }

    /**
     * @Subject
     */
    public function benchStrReplaceChainShort(): void
    {
        $result = \str_replace(['  ', "\t\t", "\n\n", "\xC2\xA0"], ' ', $this->shortText);
        while (\str_contains($result, '  ')) {
            $result = \str_replace('  ', ' ', $result);
        }
    }

    /**
     * @Subject
     */
    public function benchStrReplaceChainMedium(): void
    {
        $result = \str_replace(['  ', "\t\t", "\n\n", "\xC2\xA0"], ' ', $this->mediumText);
        while (\str_contains($result, '  ')) {
            $result = \str_replace('  ', ' ', $result);
        }
    }

    /**
     * @Subject
     */
    public function benchStrReplaceChainLong(): void
    {
        $result = \str_replace(['  ', "\t\t", "\n\n", "\xC2\xA0"], ' ', $this->longText);
        while (\str_contains($result, '  ')) {
            $result = \str_replace('  ', ' ', $result);
        }
    }

    /**
     * @Subject
     */
    public function benchStrtrShort(): void
    {
        $result = \strtr($this->shortText, ["\t" => ' ', "\n" => ' ', "\r" => ' ', "\xC2\xA0" => ' ']);
        while (\str_contains($result, '  ')) {
            $result = \str_replace('  ', ' ', $result);
        }
    }

    /**
     * @Subject
     */
    public function benchStrtrMedium(): void
    {
        $result = \strtr($this->mediumText, ["\t" => ' ', "\n" => ' ', "\r" => ' ', "\xC2\xA0" => ' ']);
        while (\str_contains($result, '  ')) {
            $result = \str_replace('  ', ' ', $result);
        }
    }

    /**
     * @Subject
     */
    public function benchStrtrLong(): void
    {
        $result = \strtr($this->longText, ["\t" => ' ', "\n" => ' ', "\r" => ' ', "\xC2\xA0" => ' ']);
        while (\str_contains($result, '  ')) {
            $result = \str_replace('  ', ' ', $result);
        }
    }

    /**
     * @Subject
     */
    public function benchMbEregReplaceShort(): void
    {
        \mb_ereg_replace('\s+', ' ', $this->shortText);
    }

    /**
     * @Subject
     */
    public function benchMbEregReplaceMedium(): void
    {
        \mb_ereg_replace('\s+', ' ', $this->mediumText);
    }

    /**
     * @Subject
     */
    public function benchMbEregReplaceLong(): void
    {
        \mb_ereg_replace('\s+', ' ', $this->longText);
    }

    /**
     * @Subject
     */
    public function benchSplitJoinShort(): void
    {
        \implode(' ', \preg_split('/\s+/', $this->shortText, -1, \PREG_SPLIT_NO_EMPTY));
    }

    /**
     * @Subject
     */
    public function benchSplitJoinMedium(): void
    {
        \implode(' ', \preg_split('/\s+/', $this->mediumText, -1, \PREG_SPLIT_NO_EMPTY));
    }

    /**
     * @Subject
     */
    public function benchSplitJoinLong(): void
    {
        \implode(' ', \preg_split('/\s+/', $this->longText, -1, \PREG_SPLIT_NO_EMPTY));
    }

    /**
     * @Subject
     */
    public function benchHybridShort(): void
    {
        $result = \str_replace(["\t", "\n", "\r", "\xC2\xA0"], ' ', $this->shortText);
        \preg_replace('/\s{2,}/', ' ', $result);
    }

    /**
     * @Subject
     */
    public function benchHybridMedium(): void
    {
        $result = \str_replace(["\t", "\n", "\r", "\xC2\xA0"], ' ', $this->mediumText);
        \preg_replace('/\s{2,}/', ' ', $result);
    }

    /**
     * @Subject
     */
    public function benchHybridLong(): void
    {
        $result = \str_replace(["\t", "\n", "\r", "\xC2\xA0"], ' ', $this->longText);
        \preg_replace('/\s{2,}/', ' ', $result);
    }
}
