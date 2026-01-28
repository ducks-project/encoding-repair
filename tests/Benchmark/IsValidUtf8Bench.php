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

use Ducks\Component\EncodingRepair\Cache\InternalArrayCache;
use Ducks\Component\EncodingRepair\CharsetProcessorInterface;
use Ducks\Component\EncodingRepair\Detector\CachedDetector;
use Ducks\Component\EncodingRepair\Detector\PregMatchDetector;
use PhpBench\Attributes\Groups;
use PhpBench\Attributes\Iterations;
use PhpBench\Attributes\Revs;
use PhpBench\Attributes\Warmup;

/**
 * Benchmark isValidUtf8 vs PregMatchDetector strategies.
 *
 * @Groups({"isvalidutf8"})
 *
 * @Revs(10000)
 *
 * @Iterations(5)
 *
 * @Warmup(2)
 *
 * @final
 */
final class IsValidUtf8Bench
{
    private string $asciiString;
    private string $utf8String;
    private string $invalidUtf8;
    private PregMatchDetector $detector;
    private CachedDetector $cachedDetector;

    public function __construct()
    {
        $this->asciiString = 'Hello World 123 ABC';
        $this->utf8String = 'Café, thé, São Paulo, Москва, 北京';
        $this->invalidUtf8 = "\xC3\x28";
        $this->detector = new PregMatchDetector();
        $this->cachedDetector = new CachedDetector(
            new PregMatchDetector(),
            new InternalArrayCache()
        );
    }

    public function benchIsValidUtf8Ascii(): void
    {
        $this->isValidUtf8($this->asciiString);
    }

    public function benchIsValidUtf8Utf8(): void
    {
        $this->isValidUtf8($this->utf8String);
    }

    public function benchIsValidUtf8Invalid(): void
    {
        $this->isValidUtf8($this->invalidUtf8);
    }

    public function benchPregMatchDetectorEqualAscii(): void
    {
        $result = $this->detector->detect($this->asciiString, null);
        $result === CharsetProcessorInterface::ENCODING_ASCII || $result === CharsetProcessorInterface::ENCODING_UTF8;
    }

    public function benchPregMatchDetectorEqualUtf8(): void
    {
        $result = $this->detector->detect($this->utf8String, null);
        $result === CharsetProcessorInterface::ENCODING_ASCII || $result === CharsetProcessorInterface::ENCODING_UTF8;
    }

    public function benchPregMatchDetectorEqualInvalid(): void
    {
        $result = $this->detector->detect($this->invalidUtf8, null);
        $result === CharsetProcessorInterface::ENCODING_ASCII || $result === CharsetProcessorInterface::ENCODING_UTF8;
    }

    public function benchPregMatchDetectorInArrayAscii(): void
    {
        $result = $this->detector->detect($this->asciiString, null);
        \in_array($result, [CharsetProcessorInterface::ENCODING_ASCII, CharsetProcessorInterface::ENCODING_UTF8], true);
    }

    public function benchPregMatchDetectorInArrayUtf8(): void
    {
        $result = $this->detector->detect($this->utf8String, null);
        \in_array($result, [CharsetProcessorInterface::ENCODING_ASCII, CharsetProcessorInterface::ENCODING_UTF8], true);
    }

    public function benchPregMatchDetectorInArrayInvalid(): void
    {
        $result = $this->detector->detect($this->invalidUtf8, null);
        \in_array($result, [CharsetProcessorInterface::ENCODING_ASCII, CharsetProcessorInterface::ENCODING_UTF8], true);
    }

    public function benchCachedDetectorEqualAscii(): void
    {
        $result = $this->cachedDetector->detect($this->asciiString, null);
        $result === CharsetProcessorInterface::ENCODING_ASCII || $result === CharsetProcessorInterface::ENCODING_UTF8;
    }

    public function benchCachedDetectorEqualUtf8(): void
    {
        $result = $this->cachedDetector->detect($this->utf8String, null);
        $result === CharsetProcessorInterface::ENCODING_ASCII || $result === CharsetProcessorInterface::ENCODING_UTF8;
    }

    public function benchCachedDetectorEqualInvalid(): void
    {
        $result = $this->cachedDetector->detect($this->invalidUtf8, null);
        $result === CharsetProcessorInterface::ENCODING_ASCII || $result === CharsetProcessorInterface::ENCODING_UTF8;
    }

    public function benchCachedDetectorInArrayAscii(): void
    {
        $result = $this->cachedDetector->detect($this->asciiString, null);
        \in_array($result, [CharsetProcessorInterface::ENCODING_ASCII, CharsetProcessorInterface::ENCODING_UTF8], true);
    }

    public function benchCachedDetectorInArrayUtf8(): void
    {
        $result = $this->cachedDetector->detect($this->utf8String, null);
        \in_array($result, [CharsetProcessorInterface::ENCODING_ASCII, CharsetProcessorInterface::ENCODING_UTF8], true);
    }

    public function benchCachedDetectorInArrayInvalid(): void
    {
        $result = $this->cachedDetector->detect($this->invalidUtf8, null);
        \in_array($result, [CharsetProcessorInterface::ENCODING_ASCII, CharsetProcessorInterface::ENCODING_UTF8], true);
    }

    private function isValidUtf8(string $string): bool
    {
        if (!\preg_match('/[\x80-\xFF]/', $string)) {
            return true;
        }
        return false !== @\preg_match('//u', $string);
    }
}
