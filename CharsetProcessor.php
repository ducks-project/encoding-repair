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
use Ducks\Component\EncodingRepair\Transcoder\IconvTranscoder;
use Ducks\Component\EncodingRepair\Transcoder\MbStringTranscoder;
use Ducks\Component\EncodingRepair\Transcoder\TranscoderChain;
use Ducks\Component\EncodingRepair\Transcoder\TranscoderInterface;
use Ducks\Component\EncodingRepair\Transcoder\UConverterTranscoder;
use InvalidArgumentException;
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

    /**
     * @var TranscoderChain
     */
    private TranscoderChain $transcoderChain;

    /**
     * @var DetectorChain
     */
    private DetectorChain $detectorChain;

    /**
     * @var list<string>
     */
    private $allowedEncodings;

    public function __construct()
    {
        $this->transcoderChain = new TranscoderChain();
        $this->detectorChain = new DetectorChain();
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
     * @inheritDoc
     */
    public function toUtf8($data, string $from = self::WINDOWS_1252, array $options = [])
    {
        return $this->toCharset($data, self::ENCODING_UTF8, $from, $options);
    }

    /**
     * @inheritDoc
     */
    public function toIso($data, string $from = self::ENCODING_UTF8, array $options = [])
    {
        return $this->toCharset($data, self::WINDOWS_1252, $from, $options);
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
        /** @var string|false $json */
        $json = \json_encode($data, $flags, $depth);

        if (false === $json) {
            throw new RuntimeException('JSON Encode Error: ' . \json_last_error_msg());
        }

        return $json;
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
        /** @var mixed $result */
        $result = \json_decode($data, $associative, $depth, $flags);

        if (null === $result && \JSON_ERROR_NONE !== \json_last_error()) {
            throw new RuntimeException('JSON Decode Error: ' . \json_last_error_msg());
        }

        return $this->toCharset($result, $to, self::ENCODING_UTF8);
    }

    /**
     * Applies a callback recursively to arrays, objects, and scalar values.
     *
     * @param mixed $data Data to process
     * @param callable $callback Processing callback function
     *
     * @return mixed
     */
    private function applyRecursive($data, callable $callback)
    {
        if (\is_array($data)) {
            /**
             * @psalm-suppress MissingClosureReturnType
             * @psalm-suppress MissingClosureParamType
             */
            return \array_map(fn ($item) => $this->applyRecursive($item, $callback), $data);
        }

        if (\is_object($data)) {
            return $this->applyToObject($data, $callback);
        }

        return $callback($data);
    }

    /**
     * Applies callback to object properties recursively.
     *
     * @param object $data Object to process
     * @param callable $callback Processing function
     *
     * @return object Cloned object with processed properties
     */
    private function applyToObject(object $data, callable $callback): object
    {
        $copy = clone $data;
        $properties = \get_object_vars($copy);

        /** @var mixed $value */
        foreach ($properties as $key => $value) {
            $copy->$key = $this->applyRecursive($value, $callback);
        }

        return $copy;
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

        if (self::ENCODING_UTF8 !== $to && $this->isValidUtf8($value)) {
            return $this->convertString($value, $to, self::ENCODING_UTF8, $options);
        }

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
        $targetEncoding = $this->resolveEncoding($to, $data, $options);
        $sourceEncoding = $this->resolveEncoding($from, $data, $options);

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

            if (null === $test || $test === $fixed || !$this->isValidUtf8($test)) {
                break;
            }

            // If conversion worked AND result is still valid UTF-8 AND result is different
            $fixed = $test;
            $iterations++;
        }

        return $fixed;
    }

    /**
     * Resolves AUTO encoding to actual encoding.
     *
     * @param string $encoding Encoding constant
     * @param string $data String for detection
     * @param array<string, mixed> $options Detection options
     *
     * @return string Resolved encoding
     *
     * @codeCoverageIgnore
     */
    private function resolveEncoding(string $encoding, string $data, array $options): string
    {
        return self::AUTO === $encoding ? $this->detect($data, $options) : $encoding;
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
