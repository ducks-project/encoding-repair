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

use Ducks\Component\EncodingRepair\Detector\CachedDetector;
use Ducks\Component\EncodingRepair\Detector\DetectorChain;
use Ducks\Component\EncodingRepair\Detector\DetectorInterface;
use Ducks\Component\EncodingRepair\Detector\FileInfoDetector;
use Ducks\Component\EncodingRepair\Detector\MbStringDetector;
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
use JsonException;
use Normalizer;
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
        $mbDetector = new MbStringDetector();
        $cachedDetector = new CachedDetector($mbDetector);
        $this->detectorChain->register($cachedDetector);
        $this->detectorChain->register(new FileInfoDetector());

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
            return self::ENCODING_ISO;
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
            return $value;
        }

        // Special handling when converting FROM UTF-8
        // Do not trust mbstring when return utf-8 but we want another encoding,
        // because it will return true even if it's not really valid.
        if (self::ENCODING_UTF8 !== $to && $this->isValidUtf8($value)) {
            return $this->convertString($value, $to, self::ENCODING_UTF8, $options);
        }

        // Check if already in target encoding
        if (\mb_check_encoding($value, $to)) {
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
            $detectedEncoding = $this->detect($data, $options);
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

        $fixed = $this->peelEncodingLayers($value, $from, $maxDepth);
        $detectedEncoding = $this->isValidUtf8($fixed) ? self::ENCODING_UTF8 : $from;

        return $this->toCharset($fixed, $to, $detectedEncoding, $options);
    }

    /**
     * Attempts to remove multiple encoding layers.
     *
     * @param string $value String to repair
     * @param string $from Encoding to reverse
     * @param int $maxDepth Maximum iterations
     *
     * @return string Repaired string
     */
    private function peelEncodingLayers(string $value, string $from, int $maxDepth): string
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

            // If conversion worked AND result is still valid UTF-8 AND result is different
            $fixed = $test;
            $iterations++;
        }

        return $fixed;
    }

    /**
     * Normalizes UTF-8 string if needed.
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
     * Checks if string is valid UTF-8.
     *
     * Please not that it will use mb_check_encoding internally,
     * and could return true also if it's not really a full utf8 string.
     *
     * @param string $string String to check
     *
     * @return bool True if valid UTF-8
     */
    private function isValidUtf8(string $string): bool
    {
        return \mb_check_encoding($string, self::ENCODING_UTF8);
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
            ['normalize' => true, 'translit' => true, 'ignore' => true, 'encodings' => self::DEFAULT_ENCODINGS],
            ...$replacements
        );
    }
}
