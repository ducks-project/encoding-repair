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

namespace Ducks\Component\EncodingRepair;

use Ducks\Component\EncodingRepair\Cleaner\CleanerChain;
use Ducks\Component\EncodingRepair\Cleaner\CleanerInterface;
use Ducks\Component\EncodingRepair\Cleaner\IconvCleaner;
use Ducks\Component\EncodingRepair\Cleaner\MbScrubCleaner;
use Ducks\Component\EncodingRepair\Cleaner\PregMatchCleaner;
use Ducks\Component\EncodingRepair\Detector\BomDetector;
use Ducks\Component\EncodingRepair\Detector\DetectorChain;
use Ducks\Component\EncodingRepair\Detector\DetectorInterface;
use Ducks\Component\EncodingRepair\Detector\FileInfoDetector;
use Ducks\Component\EncodingRepair\Detector\MbStringDetector;
use Ducks\Component\EncodingRepair\Detector\PregMatchDetector;
use Ducks\Component\EncodingRepair\Interpreter\ArrayInterpreter;
use Ducks\Component\EncodingRepair\Interpreter\InterpreterChain;
use Ducks\Component\EncodingRepair\Interpreter\ObjectInterpreter;
use Ducks\Component\EncodingRepair\Interpreter\PropertyMapperInterface;
use Ducks\Component\EncodingRepair\Interpreter\StringInterpreter;
use Ducks\Component\EncodingRepair\Interpreter\TypeInterpreterInterface;
use Ducks\Component\EncodingRepair\Transcoder\IconvTranscoder;
use Ducks\Component\EncodingRepair\Transcoder\MbStringTranscoder;
use Ducks\Component\EncodingRepair\Transcoder\TranscoderChain;
use Ducks\Component\EncodingRepair\Transcoder\TranscoderInterface;
use Ducks\Component\EncodingRepair\Transcoder\UConverterTranscoder;
use InvalidArgumentException;
use Normalizer;
use Psr\SimpleCache\CacheInterface;
use RuntimeException;

/**
 * Charset processing service.
 *
 * @final
 */
final class CharsetProcessor implements CharsetProcessorInterface
{
    private const DEFAULT_ENCODINGS = [
        self::ENCODING_UTF8,
        self::WINDOWS_1252,
        self::ENCODING_ISO,
        self::ENCODING_ASCII,
    ];

    private const MAX_REPAIR_DEPTH = 5;
    private const JSON_DEFAULT_DEPTH = 512;
    private const DEFAULT_MAX_SAMPLES = 1;

    /**
     * @var TranscoderChain
     */
    private TranscoderChain $transcoderChain;

    /**
     * @var DetectorChain
     */
    private DetectorChain $detectorChain;

    /**
     * @var CleanerChain
     */
    private CleanerChain $cleanerChain;

    /**
     * @var InterpreterChain
     */
    private InterpreterChain $interpreterChain;

    /**
     * @var list<string>
     */
    private $allowedEncodings;

    public function __construct()
    {
        $this->transcoderChain = new TranscoderChain();
        $this->detectorChain = new DetectorChain();
        $this->cleanerChain = new CleanerChain();
        $this->interpreterChain = new InterpreterChain();
        $this->allowedEncodings = [
            self::AUTO,
            self::ENCODING_UTF8,
            self::WINDOWS_1252,
            self::ENCODING_ISO,
            self::ENCODING_ASCII,
            self::ENCODING_UTF16,
            self::ENCODING_UTF32,
        ];

        $this->resetTranscoders();
        $this->resetDetectors();
        $this->resetCleaners();
        $this->resetInterpreters();
    }

    /**
     * @inheritDoc
     */
    public function registerTranscoder(TranscoderInterface $transcoder, ?int $priority = null): self
    {
        $this->transcoderChain->register($transcoder, $priority);

        return $this;
    }

    /**
     * @inheritDoc
     */
    public function unregisterTranscoder(TranscoderInterface $transcoder): self
    {
        $this->transcoderChain->unregister($transcoder);

        return $this;
    }

    /**
     * @inheritDoc
     */
    public function queueTranscoders(TranscoderInterface ...$transcoders): self
    {
        foreach ($transcoders as $transcoder) {
            $this->registerTranscoder($transcoder);
        }

        return $this;
    }

    /**
     * @inheritDoc
     */
    public function resetTranscoders(): self
    {
        $this->transcoderChain = new TranscoderChain();
        $this->transcoderChain->register(new UConverterTranscoder());
        $this->transcoderChain->register(new IconvTranscoder());
        $this->transcoderChain->register(new MbStringTranscoder());

        return $this;
    }

    /**
     * @inheritDoc
     */
    public function registerDetector(DetectorInterface $detector, ?int $priority = null): self
    {
        $this->detectorChain->register($detector, $priority);

        return $this;
    }

    /**
     * @inheritDoc
     */
    public function unregisterDetector(DetectorInterface $detector): self
    {
        $this->detectorChain->unregister($detector);

        return $this;
    }

    /**
     * @inheritDoc
     */
    public function queueDetectors(DetectorInterface ...$detectors): self
    {
        foreach ($detectors as $detector) {
            $this->registerDetector($detector);
        }

        return $this;
    }

    /**
     * @inheritDoc
     */
    public function resetDetectors(): self
    {
        $this->detectorChain = new DetectorChain();
        $this->detectorChain->enableCache();

        // BomDetector: 100% accurate when BOM present (priority: 160)
        $this->detectorChain->register(new BomDetector());

        // PregMatchDetector: Fast ASCII/UTF-8 detection (priority: 150)
        $this->detectorChain->register(new PregMatchDetector());

        // MbStringDetector: Statistical detection (priority: 100)
        $this->detectorChain->register(new MbStringDetector());

        // FileInfoDetector: Fallback (priority: 50)
        $this->detectorChain->register(new FileInfoDetector());

        return $this;
    }

    /**
     * Enable detection caching for improved performance.
     *
     * @param CacheInterface|null $cache PSR-16 cache (default: InternalArrayCache)
     * @param int $ttl Cache TTL in seconds (default: 3600)
     *
     * @return self
     *
     * @psalm-api
     */
    public function enableDetectionCache(
        ?CacheInterface $cache = null,
        int $ttl = 3600
    ): self {
        $this->detectorChain->enableCache($cache, $ttl);

        return $this;
    }

    /**
     * Disable detection caching.
     *
     * @return self
     *
     * @psalm-api
     */
    public function disableDetectionCache(): self
    {
        $this->detectorChain->disableCache();

        return $this;
    }

    /**
     * Clear detection cache.
     *
     * @return self
     *
     * @psalm-api
     */
    public function clearDetectionCache(): self
    {
        $this->detectorChain->clearCache();

        return $this;
    }

    /**
     * @inheritDoc
     */
    public function registerCleaner(CleanerInterface $cleaner, ?int $priority = null): self
    {
        $this->cleanerChain->register($cleaner, $priority);

        return $this;
    }

    /**
     * @inheritDoc
     */
    public function unregisterCleaner(CleanerInterface $cleaner): self
    {
        $this->cleanerChain->unregister($cleaner);

        return $this;
    }

    /**
     * @inheritDoc
     */
    public function resetCleaners(): self
    {
        $this->cleanerChain = new CleanerChain();
        $this->cleanerChain->register(new MbScrubCleaner());
        $this->cleanerChain->register(new PregMatchCleaner());
        $this->cleanerChain->register(new IconvCleaner());

        return $this;
    }

    /**
     * @inheritDoc
     */
    public function addEncodings(string ...$encodings): self
    {
        foreach ($encodings as $encoding) {
            if (!\in_array($encoding, $this->allowedEncodings, true)) {
                $this->allowedEncodings[] = $encoding;
            }
        }

        return $this;
    }

    /**
     * @inheritDoc
     */
    public function removeEncodings(string ...$encodings): self
    {
        $this->allowedEncodings = \array_values(
            \array_diff($this->allowedEncodings, $encodings)
        );

        return $this;
    }

    /**
     * @inheritDoc
     */
    public function getEncodings(): array
    {
        return $this->allowedEncodings;
    }

    /**
     * @inheritDoc
     */
    public function resetEncodings(): self
    {
        $this->allowedEncodings = [
            self::AUTO,
            self::ENCODING_UTF8,
            self::WINDOWS_1252,
            self::ENCODING_ISO,
            self::ENCODING_ASCII,
            self::ENCODING_UTF16,
            self::ENCODING_UTF32,
        ];

        return $this;
    }

    /**
     * @inheritDoc
     */
    public function registerInterpreter(TypeInterpreterInterface $interpreter, ?int $priority = null): self
    {
        $this->interpreterChain->register($interpreter, $priority);

        return $this;
    }

    /**
     * @inheritDoc
     */
    public function unregisterInterpreter(TypeInterpreterInterface $interpreter): self
    {
        $this->interpreterChain->unregister($interpreter);

        return $this;
    }

    /**
     * @inheritDoc
     */
    public function registerPropertyMapper(string $className, PropertyMapperInterface $mapper): self
    {
        $objectInterpreter = $this->interpreterChain->getObjectInterpreter();

        if (null === $objectInterpreter) {
            throw new RuntimeException('ObjectInterpreter not registered in chain');
        }

        $objectInterpreter->registerMapper($className, $mapper);

        return $this;
    }

    /**
     * @inheritDoc
     */
    public function resetInterpreters(): self
    {
        $this->interpreterChain = new InterpreterChain();
        $this->interpreterChain->register(new StringInterpreter(), 100);
        $this->interpreterChain->register(new ArrayInterpreter($this->interpreterChain), 50);
        $this->interpreterChain->register(new ObjectInterpreter($this->interpreterChain), 30);

        return $this;
    }

    /**
     * @inheritDoc
     */
    public function is(string $string, string $encoding, array $options = []): bool
    {
        $this->validateEncoding($encoding, 'target');

        $detected = $this->detect($string, $options);
        $normalized = \strtoupper($encoding);

        // Direct match
        if ($detected === $encoding || $detected === $normalized) {
            return true;
        }

        // Handle aliases: CP1252 = Windows-1252 = ISO-8859-1
        $aliases = [
            self::WINDOWS_1252 => [self::ENCODING_ISO, 'WINDOWS-1252'],
            self::ENCODING_ISO => [self::WINDOWS_1252, 'WINDOWS-1252'],
        ];

        if (isset($aliases[$encoding])) {
            return \in_array($detected, $aliases[$encoding], true);
        }

        if (isset($aliases[$normalized])) {
            return \in_array($detected, $aliases[$normalized], true);
        }

        return false;
    }

    /**
     * @inheritDoc
     */
    public function detect(string $string, array $options = []): string
    {
        if ($this->isValidUtf8($string)) {
            return self::ENCODING_UTF8;
        }

        $detected = $this->detectorChain->detect($string, $options);

        return $detected ?? self::ENCODING_ISO;
    }

    /**
     * @inheritDoc
     */
    public function detectBatch(iterable $items, array $options = []): string
    {
        /** @var mixed $maxSamples */
        $maxSamples = $options['maxSamples'] ?? self::DEFAULT_MAX_SAMPLES;
        if (!\is_int($maxSamples) || 1 > $maxSamples) {
            $maxSamples = self::DEFAULT_MAX_SAMPLES;
        }

        /** @var list<string> $samples */
        $samples = [];

        /** @var mixed $item */
        foreach ($items as $item) {
            if (\is_string($item) && '' !== $item) {
                $samples[] = $item;
                if (\count($samples) >= $maxSamples) {
                    break;
                }
            }
        }

        // Fast return.
        if (empty($samples)) {
            return self::ENCODING_UTF8;
        }

        // Fast path: single sample (default behavior)
        if (1 === $maxSamples) {
            return $this->detect($samples[0], $options);
        }

        // Detect on longest sample (more reliable for multiple samples)
        $longest = \array_reduce(
            $samples,
            /**
             * @param null|string $carry
             * @param string $item
             */
            static fn ($carry, $item) => \strlen($item) > \strlen($carry ?? '') ? $item : $carry
        );

        return $this->detect($longest, $options);
    }

    /**
     * @inheritDoc
     */
    public function toCharset(
        $data,
        string $to = self::ENCODING_UTF8,
        string $from = self::ENCODING_ISO,
        array $options = []
    ) {
        $this->validateEncoding($to, 'target');
        $this->validateEncoding($from, 'source');

        $options = $this->configureOptions($options);

        // We define the callback logic for a single string
        /**
         * @psalm-suppress MissingClosureParamType
         * @psalm-suppress MissingClosureReturnType
         */
        $callback = fn ($value) => $this->convertValue($value, $to, $from, $options);

        return $this->applyRecursive($data, $callback);
    }

    /**
     * Converts anything (string, array, object) to UTF-8.
     *
     * @param mixed $data Data to convert
     * @param string $from Source encoding
     * @param array<string, mixed> $options Conversion options
     *                                      - 'normalize': bool (default: true)
     *                                      - 'translit': bool (default: true)
     *                                      - 'ignore': bool (default: true)
     *
     * @return mixed
     *
     * @throws InvalidArgumentException If encoding is invalid
     *
     * @psalm-api
     */
    public function toUtf8($data, string $from = self::WINDOWS_1252, array $options = [])
    {
        return $this->toCharset($data, self::ENCODING_UTF8, $from, $options);
    }

    /**
     * Converts anything to ISO-8859-1 (Windows-1252).
     *
     * @param mixed $data Data to convert
     * @param string $from Source encoding
     * @param array<string, mixed> $options Conversion options
     *                                      - 'normalize': bool (default: true)
     *                                      - 'translit': bool (default: true)
     *                                      - 'ignore': bool (default: true)
     *
     * @return mixed
     *
     * @throws InvalidArgumentException If encoding is invalid
     *
     * @psalm-api
     */
    public function toIso($data, string $from = self::ENCODING_UTF8, array $options = [])
    {
        return $this->toCharset($data, self::WINDOWS_1252, $from, $options);
    }

    /**
     * @inheritDoc
     */
    public function toCharsetBatch(
        array $items,
        string $to = self::ENCODING_UTF8,
        string $from = self::ENCODING_ISO,
        array $options = []
    ): array {
        $this->validateEncoding($to, 'target');
        $this->validateEncoding($from, 'source');

        if (self::AUTO === $from) {
            $from = $this->detectBatch($items, $options);
        }

        /** @psalm-suppress MissingClosureReturnType */
        return \array_map(fn ($item) => $this->toCharset($item, $to, $from, $options), $items);
    }

    /**
     * Batch convert array items from one encoding to utf8.
     *
     * Optimized for homogeneous arrays: detects encoding once on first non-empty string.
     * Use this instead of toUtf8() when processing large arrays with AUTO detection.
     *
     * @param array<mixed> $items Items to convert
     * @param string $from Source encoding (use AUTO for detection)
     * @param array<string, mixed> $options Conversion options
     *
     * @return array<mixed> Converted items
     *
     * @throws InvalidArgumentException If encoding is
     *
     * @psalm-api
     */
    public function toUtf8Batch(
        array $items,
        string $from = self::WINDOWS_1252,
        array $options = []
    ): array {
        return $this->toCharsetBatch($items, self::ENCODING_UTF8, $from, $options);
    }

    /**
     * Batch convert array items from one encoding to iso.
     *
     * Optimized for homogeneous arrays: detects encoding once on first non-empty string.
     * Use this instead of toIso() when processing large arrays with AUTO detection.
     *
     * @param array<mixed> $items Items to convert
     * @param string $from Source encoding (use AUTO for detection)
     * @param array<string, mixed> $options Conversion options
     *
     * @return array<mixed> Converted items
     *
     * @throws InvalidArgumentException If encoding is invalid
     *
     * @psalm-api
     */
    public function toIsoBatch(
        array $items,
        string $from = self::ENCODING_UTF8,
        array $options = []
    ): array {
        return $this->toCharsetBatch($items, self::WINDOWS_1252, $from, $options);
    }

    /**
     * @inheritDoc
     */
    public function repair(
        $data,
        string $to = self::ENCODING_UTF8,
        string $from = self::ENCODING_ISO,
        array $options = []
    ) {
        $options = $this->configureOptions($options, ['maxDepth' => self::MAX_REPAIR_DEPTH]);

        /**
         * @psalm-suppress MissingClosureParamType
         * @psalm-suppress MissingClosureReturnType
         */
        $callback = fn ($value) => $this->repairValue($value, $to, $from, $options);

        return $this->applyRecursive($data, $callback);
    }

    /**
     * @inheritDoc
     */
    public function safeJsonEncode(
        $data,
        int $flags = 0,
        int $depth = self::JSON_DEFAULT_DEPTH,
        string $from = self::WINDOWS_1252
    ): string {
        /** @var mixed $data */
        $data = $this->repair($data, self::ENCODING_UTF8, $from);

        // Force JSON_THROW_ON_ERROR flag
        return \json_encode($data, $flags | \JSON_THROW_ON_ERROR, $depth);
    }

    /**
     * @inheritDoc
     */
    public function safeJsonDecode(
        string $json,
        ?bool $associative = null,
        int $depth = self::JSON_DEFAULT_DEPTH,
        int $flags = 0,
        string $to = self::ENCODING_UTF8,
        string $from = self::WINDOWS_1252
    ) {
        // Repair string to a valid UTF-8 for decoding
        /** @var string $data */
        $data = $this->repair($json, self::ENCODING_UTF8, $from);

        // Force JSON_THROW_ON_ERROR flag
        /** @var mixed $result */
        $result = \json_decode($data, $associative, $depth, $flags | \JSON_THROW_ON_ERROR);

        return $this->toCharset($result, $to, self::ENCODING_UTF8);
    }

    /**
     * Applies a callback recursively using type interpreters.
     *
     * @param mixed $data Data to process
     * @param callable $callback Processing callback function
     *
     * @return mixed
     */
    private function applyRecursive($data, callable $callback)
    {
        return $this->interpreterChain->interpret($data, $callback, []);
    }

    /**
     * Converts a single value to target encoding.
     *
     * @param mixed $value Value to convert
     * @param string $to Target encoding
     * @param string $from Source encoding
     * @param array<string, mixed> $options Conversion configuration
     *
     * @return mixed
     */
    private function convertValue($value, string $to, string $from, array $options)
    {
        if (!\is_string($value)) {
            // @codeCoverageIgnoreStart
            return $value;
            // @codeCoverageIgnoreEnd
        }

        // Special handling when converting FROM UTF-8
        // Do not trust mbstring when return utf-8 but we want another encoding,
        // because it will return true even if it's not really valid.
        if (self::ENCODING_UTF8 !== $to && $this->isValidUtf8($value)) {
            return $this->convertString($value, $to, self::ENCODING_UTF8, $options);
        }

        // Check if already in target encoding
        if (\mb_check_encoding($value, $to)) {
            // normalize will just return $value on non-utf8.
            return $this->normalize($value, $to, $options);
        }

        return $this->convertString($value, $to, $from, $options);
    }

    /**
     * Low-level string conversion logic.
     *
     * @param string $data String to convert
     * @param string $to Target encoding
     * @param string $from Source encoding
     * @param array<string, mixed> $options Conversion options
     *
     * @return string Converted string or $data if convertion failed
     */
    private function convertString(string $data, string $to, string $from, array $options): string
    {
        if (true === ($options['clean'] ?? false)) {
            $cleaned = $this->cleanerChain->clean($data, $to, $options);
            $data = $cleaned ?? $data;
        }

        return $this->transcodeString($data, $to, $from, $options) ?? $data;
    }

    /**
     * Low-level string transcode logic with fallback strategies.
     *
     * @param string $data String to transcode
     * @param string $to Target encoding
     * @param string $from Source encoding
     * @param array<string, mixed> $options Conversion options
     *
     * @return ?string Converted string or null if failed.
     */
    private function transcodeString(string $data, string $to, string $from, array $options): ?string
    {
        // Optimize: detect once if both are AUTO
        $detectedEncoding = null;
        if (self::AUTO === $to || self::AUTO === $from) {
            // @codeCoverageIgnoreStart
            $detectedEncoding = $this->detect($data, $options);
            // @codeCoverageIgnoreEnd
        }

        /** @var string $targetEncoding */
        $targetEncoding = self::AUTO === $to ? $detectedEncoding : $to;
        /** @var string $sourceEncoding */
        $sourceEncoding = self::AUTO === $from ? $detectedEncoding : $from;

        $result = $this->transcoderChain->transcode($data, $targetEncoding, $sourceEncoding, $options);

        if (null !== $result && self::ENCODING_UTF8 === $targetEncoding) {
            return $this->normalize($result, $targetEncoding, $options);
        }

        return $result;
    }

    /**
     * Repairs a double-encoded value.
     *
     * @param mixed $value Value to repair
     * @param string $to Target encoding
     * @param string $from Glitch encoding
     * @param array<string, mixed> $options Configuration
     *
     * @return mixed
     */
    private function repairValue($value, string $to, string $from, array $options)
    {
        if (!\is_string($value)) {
            // @codeCoverageIgnoreStart
            return $value;
            // @codeCoverageIgnoreEnd
        }

        /** @var mixed $maxDepth */
        $maxDepth = $options['maxDepth'] ?? self::MAX_REPAIR_DEPTH;
        if (!\is_int($maxDepth)) {
            $maxDepth = self::MAX_REPAIR_DEPTH;
        }

        // Enable cleaning by default for repair
        $options['clean'] = $options['clean'] ?? true;

        $fixed = $this->peelEncodingLayers($value, $from, $maxDepth, $options);
        $detectedEncoding = $this->isValidUtf8($fixed) ? self::ENCODING_UTF8 : $from;

        return $this->toCharset($fixed, $to, $detectedEncoding, $options);
    }

    /**
     * Attempts to remove multiple encoding layers.
     *
     * @param string $value String to repair
     * @param string $from Encoding to reverse
     * @param int $maxDepth Maximum iterations
     * @param array<string, mixed> $options Configuration
     *
     * @return string Repaired string
     */
    private function peelEncodingLayers(string $value, string $from, int $maxDepth, array $options): string
    {
        // Clean invalid sequences if enabled
        if (true === ($options['clean'] ?? true)) {
            $cleaned = $this->cleanerChain->clean($value, self::ENCODING_UTF8, $options);
            $value = $cleaned ?? $value;
        }

        // Quick check: if no corruption patterns, return as-is
        if (false === \strpos($value, "\xC3\x82") && false === \strpos($value, "\xC3\x83")) {
            return $value;
        }

        // Try transcode repair
        $fixed = $this->repairByTranscode($value, $from, $maxDepth);

        // Try pattern-based repair (ForceUTF8 approach)
        if ($fixed === $value || false !== \strpos($fixed, "\xC3\x82")) {
            $fixed = $this->repairByPatternReplacement($fixed);
        }

        return $fixed;
    }

    /**
     * Repairs UTF-8 strings using trancoding approch.
     *
     * @param string $value String to repair
     * @param string $from Encoding to reverse
     * @param int $maxDepth Maximum iterations
     *
     * @return string Repaired string
     */
    private function repairByTranscode(string $value, string $from, int $maxDepth): string
    {
        $fixed = $value;
        $iterations = 0;
        $options = ['normalize' => false, 'translit' => false, 'ignore' => false];

        // Loop while it looks like valid UTF-8
        while ($iterations < $maxDepth && $this->isValidUtf8($fixed)) {
            // Attempt to reverse convert (UTF-8 -> $from)
            $test = $this->transcodeString($fixed, $from, self::ENCODING_UTF8, $options);

            // Break if conversion failed, no change, or result is longer (infinite loop detection)
            if (null === $test || $test === $fixed || \strlen($test) >= \strlen($fixed) || !$this->isValidUtf8($test)) {
                break;
            }

            // If conversion worked
            // AND result is still valid UTF-8
            // AND result is different
            // AND result is shorter,
            // it's a progress.
            $fixed = $test;
            $iterations++;
        }

        return $fixed;
    }

    /**
     * Repairs UTF-8 strings using pattern replacement (ForceUTF8 approach).
     *
     * @param string $value String to repair
     *
     * @return string Repaired string
     */
    private function repairByPatternReplacement(string $value): string
    {
        // Optimized with single preg_replace call (30-40% faster than 2 calls)
        return \preg_replace(
            ['/\xC3\x82/', '/\xC3\x83\xC2([\xA0-\xFF])/'],
            ['', "\xC3$1"],
            $value
        ) ?? $value;
    }

    /**
     * Normalizes UTF-8 string if needed.
     *
     * It will return the value as it on non-utf8 string.
     *
     * @param string $value String to normalize
     * @param string $to Target encoding
     * @param array<string, mixed> $options Configuration
     *
     * @return string Normalized or original string
     *
     * @codeCoverageIgnore
     */
    private function normalize(string $value, string $to, array $options): string
    {
        // Only normalize if: target is UTF-8 AND normalize option is true
        if (self::ENCODING_UTF8 !== $to || false === ($options['normalize'] ?? true)) {
            return $value;
        }

        if (!\class_exists(Normalizer::class)) {
            return $value;
        }

        $normalized = Normalizer::normalize($value);

        return false !== $normalized ? $normalized : $value;
    }

    /**
     * Fast checks if string is valid UTF-8.
     *
     * Be carrefull for corrupted strings, because it
     * can return true (for example : BrÃ©sil).
     *
     * Uses optimized fast-path for ASCII-only strings (~35% faster),
     * then preg_match with 'u' modifier for UTF-8 validation
     * (~60% faster than mb_check_encoding).
     * The PCRE engine validates UTF-8 sequences efficiently in C.
     *
     * @param string $string String to check
     *
     * @return bool True if valid UTF-8
     */
    private function isValidUtf8(string $string): bool
    {
        // Fast-path: ASCII-only strings (0x00-0x7F) are always valid UTF-8
        if (!\preg_match('/[\x80-\xFF]/', $string)) {
            return true;
        }

        // Full UTF-8 validation for non-ASCII strings
        return false !== @\preg_match('//u', $string);
    }

    /**
     * Validates encoding name against whitelist.
     *
     * @param string $encoding Encoding to validate
     * @param string $type Type for error message (e.g., 'source', 'target')
     *
     * @throws InvalidArgumentException If encoding is not allowed
     */
    private function validateEncoding(string $encoding, string $type): void
    {
        $normalized = \strtoupper($encoding);

        if (
            !\in_array($encoding, $this->allowedEncodings, true)
            && !\in_array($normalized, $this->allowedEncodings, true)
        ) {
            throw new InvalidArgumentException(
                \sprintf(
                    'Invalid %s encoding: "%s". Allowed: %s',
                    $type,
                    $encoding,
                    \implode(', ', $this->allowedEncodings)
                )
            );
        }
    }

    /**
     * Builds conversion configuration with defaults.
     *
     * Merges user options with default values, allowing multiple override layers.
     *
     * @param array<string, mixed> $options User-provided options
     * @param array<string, mixed> ...$replacements Additional override layers
     *
     * @return array<string, mixed> Merged configuration
     *
     * @example
     * // Basic usage
     * $config = self::configureOptions(['normalize' => false]);
     *
     * // With additional defaults
     * $config = self::configureOptions(
     *     ['normalize' => false],
     *     ['maxDepth' => 10]
     * );
     */
    private function configureOptions(array $options, array ...$replacements): array
    {
        $replacements[] = $options;

        return \array_replace(
            [
                'normalize' => true,
                'translit' => true,
                'ignore' => true,
                'clean' => false,
                'encodings' => self::DEFAULT_ENCODINGS,
            ],
            ...$replacements
        );
    }
}
