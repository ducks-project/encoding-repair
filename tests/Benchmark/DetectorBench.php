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

use Ducks\Component\EncodingRepair\Detector\CachedDetector;
use Ducks\Component\EncodingRepair\Detector\FileInfoDetector;
use Ducks\Component\EncodingRepair\Detector\MbStringDetector;
use Ducks\Component\EncodingRepair\Tests\Common\Phrase;

/**
 * @Groups({"detector"})
 *
 * @Revs(1000)
 *
 * @Iterations(5)
 *
 * @Warmup(2)
 */
final class DetectorBench
{
    private string $utf8String;

    private MbStringDetector $mbDetector;
    private FileInfoDetector $fileInfoDetector;
    private CachedDetector $cachedDetector;

    public function __construct()
    {
        $phrase = new Phrase();

        $this->utf8String = (string) $phrase;
        $this->mbDetector = new MbStringDetector();
        $this->fileInfoDetector = new FileInfoDetector();
        $this->cachedDetector = new CachedDetector($this->mbDetector);
    }

    /**
     * @Subject
     */
    public function benchMbStringDetector(): void
    {
        $this->mbDetector->detect($this->utf8String);
    }

    /**
     * @Subject
     */
    public function benchFileInfoDetector(): void
    {
        $this->fileInfoDetector->detect($this->utf8String);
    }

    /**
     * @Subject
     */
    public function benchCachedDetector(): void
    {
        $this->cachedDetector->detect($this->utf8String);
    }

    /**
     * @Subject
     */
    public function benchCachedDetectorHit(): void
    {
        $this->cachedDetector->detect($this->utf8String);
        $this->cachedDetector->detect($this->utf8String);
    }
}
