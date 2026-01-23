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

/**
 * @Groups({"batch"})
 *
 * @Revs(100)
 *
 * @Iterations(5)
 *
 * @Warmup(2)
 */
final class BatchBench
{
    /**
     * @var array<int, string>
     */
    private array $smallBatch;

    /**
     * @var array<int, string>
     */
    private array $mediumBatch;

    /**
     * @var array<int, string>
     */
    private array $largeBatch;

    public function __construct()
    {
        $this->smallBatch = array_fill(0, 10, 'Café résumé');
        $this->mediumBatch = array_fill(0, 100, 'Café résumé');
        $this->largeBatch = array_fill(0, 1000, 'Café résumé');
    }

    /**
     * @Subject
     */
    public function benchSmallBatchConversion(): void
    {
        CharsetHelper::toCharsetBatch($this->smallBatch, CharsetHelper::ENCODING_UTF8, CharsetHelper::WINDOWS_1252);
    }

    /**
     * @Subject
     */
    public function benchSmallLoopConversion(): void
    {
        foreach ($this->smallBatch as $item) {
            CharsetHelper::toCharset($item, CharsetHelper::ENCODING_UTF8, CharsetHelper::WINDOWS_1252);
        }
    }

    /**
     * @Subject
     */
    public function benchMediumBatchConversion(): void
    {
        CharsetHelper::toCharsetBatch($this->mediumBatch, CharsetHelper::ENCODING_UTF8, CharsetHelper::WINDOWS_1252);
    }

    /**
     * @Subject
     */
    public function benchMediumLoopConversion(): void
    {
        foreach ($this->mediumBatch as $item) {
            CharsetHelper::toCharset($item, CharsetHelper::ENCODING_UTF8, CharsetHelper::WINDOWS_1252);
        }
    }

    /**
     * @Subject
     */
    public function benchLargeBatchConversion(): void
    {
        CharsetHelper::toCharsetBatch($this->largeBatch, CharsetHelper::ENCODING_UTF8, CharsetHelper::WINDOWS_1252);
    }

    /**
     * @Subject
     */
    public function benchLargeLoopConversion(): void
    {
        foreach ($this->largeBatch as $item) {
            CharsetHelper::toCharset($item, CharsetHelper::ENCODING_UTF8, CharsetHelper::WINDOWS_1252);
        }
    }

    /**
     * @Subject
     */
    public function benchBatchDetection(): void
    {
        CharsetHelper::detectBatch($this->mediumBatch);
    }
}
