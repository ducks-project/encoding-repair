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

use Ducks\Component\EncodingRepair\Transcoder\TranscoderChain;
use Ducks\Component\EncodingRepair\Transcoder\TranscoderInterface;
use Ducks\Component\EncodingRepair\Transcoder\UConverterTranscoder;
use Ducks\Component\EncodingRepair\Transcoder\IconvTranscoder;
use Ducks\Component\EncodingRepair\Transcoder\MbStringTranscoder;
use Ducks\Component\EncodingRepair\Transcoder\CallableTranscoder;
use Ducks\Component\EncodingRepair\Detector\DetectorChain;
use Ducks\Component\EncodingRepair\Detector\DetectorInterface;
use Ducks\Component\EncodingRepair\Detector\MbStringDetector;
use Ducks\Component\EncodingRepair\Detector\FileInfoDetector;
use Ducks\Component\EncodingRepair\Detector\CallableDetector;
use InvalidArgumentException;
use Normalizer;
use RuntimeException;

/**
 * Helper class for encoding and detect charset.
 *
 * Designed to handle legacy ISO-8859-1 <-> UTF-8 interoperability issues.
 * Implements Chain of Responsibility pattern for extensibility.
 *
 * @psalm-api
 *
 * @psalm-immutable This class has no mutable state
 *
 * @final
 */
final class CharsetHelper
{
    public const AUTO = 'AUTO';
    public const WINDOWS_1252 = 'CP1252';
    public const ENCODING_ISO = 'ISO-8859-1';
    public const ENCODING_UTF8 = 'UTF-8';
    public const ENCODING_UTF16 = 'UTF-16';
    public const ENCODING_UTF32 = 'UTF-32';
    public const ENCODING_ASCII = 'ASCII';

    private const DEFAULT_ENCODINGS = [
        self::ENCODING_UTF8,
        self::WINDOWS_1252,
        self::ENCODING_ISO,
        self::ENCODING_ASCII,
    ];

    private const ALLOWED_ENCODINGS = [
        self::AUTO,
        self::ENCODING_UTF8,
        self::WINDOWS_1252,
        self::ENCODING_ISO,
        self::ENCODING_ASCII,
        self::ENCODING_UTF16,
        self::ENCODING_UTF32,
    ];

    private const MAX_REPAIR_DEPTH = 5;
    private const JSON_DEFAULT_DEPTH = 512;

    /**
     * Transcoder chain instance.
     *
     * @var TranscoderChain|null
     */
    private static $transcoderChain = null;

    /**
     * Detector chain instance.
     *
     * @var DetectorChain|null
     */
    private static $detectorChain = null;

    /**
     * Private constructor to prevent instantiation of static utility class.
     *
     * @psalm-api
     *
     * @codeCoverageIgnore
     */
    private function __construct()
    {
    }

    /**
     * Get or initialize transcoder chain.
     *
     * @return TranscoderChain
     */
    private static function getTranscoderChain(): TranscoderChain
    {
        if (null === self::$transcoderChain) {
            self::$transcoderChain = new TranscoderChain();
            self::$transcoderChain->register(new UConverterTranscoder());
            self::$transcoderChain->register(new IconvTranscoder());
            self::$transcoderChain->register(new MbStringTranscoder());
        }

        return self::$transcoderChain;
    }

    /**
     * Get or initialize detector chain.
     *
     * @return DetectorChain
     */
    private static function getDetectorChain(): DetectorChain
    {
        if (null === self::$detectorChain) {
            self::$detectorChain = new DetectorChain();
            self::$detectorChain->register(new MbStringDetector());
            self::$detectorChain->register(new FileInfoDetector());
        }

        return self::$detectorChain;
    }

    /**
     * Register a transcoder with optional priority.
     *
     * @phpcs:disable Generic.Files.LineLength.TooLong
     *
     * @param TranscoderInterface|callable(string, string, string, null|array<string, mixed>): (string|null) $transcoder Transcoder instance or callable
     * @param int|null $priority Priority override (null = use transcoder's default)
     *
     * @return void
     *
     * @throws InvalidArgumentException If transcoder is invalid
     *
     * @phpcs:enable Generic.Files.LineLength.TooLong
     */
    public static function registerTranscoder(
        $transcoder,
        ?int $priority = null
    ): void {
        /** @var mixed $transcoder */
        if ($transcoder instanceof TranscoderInterface) {
            // @codeCoverageIgnoreStart
            self::getTranscoderChain()->register($transcoder, $priority);
            return;
            // @codeCoverageIgnoreEnd
        }

        if (\is_callable($transcoder)) {
            /** @var callable(string, string, string, null|array<string, mixed>): (string|null) $transcoder */
            $wrapper = new CallableTranscoder($transcoder, $priority ?? 0);
            self::getTranscoderChain()->register($wrapper, $priority);
            return;
        }

        throw new InvalidArgumentException(
            'Transcoder must be an instance of TranscoderInterface or a callable'
        );
    }

    /**
     * Register a detector with optional priority.
     *
     * @phpcs:disable Generic.Files.LineLength.TooLong
     *
     * @param DetectorInterface|callable(string, array<string, mixed>|null): (string|null) $detector Detector instance or callable
     * @param int|null $priority Priority override (null = use detector's default)
     *
     * @return void
     *
     * @throws InvalidArgumentException If detector is invalid
     *
     * @phpcs:enable Generic.Files.LineLength.TooLong
     */
    public static function registerDetector(
        $detector,
        ?int $priority = null
    ): void {
        /** @var mixed $detector */
        if ($detector instanceof DetectorInterface) {
            // @codeCoverageIgnoreStart
            self::getDetectorChain()->register($detector, $priority);
            return;
            // @codeCoverageIgnoreEnd
        }

        if (\is_callable($detector)) {
            /** @var callable(string, array<string, mixed>|null): (string|null) $detector */
            $wrapper = new CallableDetector($detector, $priority ?? 0);
            self::getDetectorChain()->register($wrapper, $priority);
            return;
        }

        throw new InvalidArgumentException(
            'Detector must be an instance of DetectorInterface or a callable'
        );
    }

    /**
     * Detects the charset encoding of a string.
     *
     * @param string $string String to analyze
     * @param array<string, mixed> $options Conversion options
     *                                      - 'encodings': array of encodings to test
     *
     * @return string Detected encoding (uppercase)
     */
    public static function detect(string $string, array $options = []): string
    {
        // Fast common return.
        if (self::isValidUtf8($string)) {
            return self::ENCODING_UTF8;
        }

        $detected = self::getDetectorChain()->detect($string, $options);

        return $detected ?? self::ENCODING_ISO;
    }

    /**
     * Convert $data string from one encoding to another.
     *
     * @param mixed $data Data to convert
     * @param string $to Target encoding
     * @param string $from Source encoding (use AUTO for detection)
     * @param array<string, mixed> $options Conversion options
     *                                      - 'normalize': bool (default: true)
     *                                      - 'translit': bool (default: true)
     *                                      - 'ignore': bool (default: true)
     *
     * @return mixed The data transcoded in the target encoding
     *
     * @throws InvalidArgumentException If encoding is invalid
     */
    public static function toCharset(
        $data,
        string $to = self::ENCODING_UTF8,
        string $from = self::ENCODING_ISO,
        array $options = []
    ) {
        self::validateEncoding($to, 'target');
        self::validateEncoding($from, 'source');

        $options = self::configureOptions($options);

        // We define the callback logic for a single string
        /**
         * @psalm-suppress MissingClosureParamType
         * @psalm-suppress MissingClosureReturnType
         */
        $callback = static fn ($value) => self::convertValue($value, $to, $from, $options);

        return self::applyRecursive($data, $callback);
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
     */
    public static function toUtf8(
        $data,
        string $from = self::WINDOWS_1252,
        array $options = []
    ) {
        return self::toCharset(
            $data,
            self::ENCODING_UTF8,
            $from,
            $options
        );
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
     */
    public static function toIso(
        $data,
        string $from = self::ENCODING_UTF8,
        array $options = []
    ) {
        return self::toCharset(
            $data,
            self::WINDOWS_1252,
            $from,
            $options
        );
    }

    /**
     * Repairs double-encoded strings.
     *
     * Attempts to fix strings that have been encoded multiple times
     * by detecting and reversing the encoding layers.
     * Pay attention that it will first repair within UTF-8, then converts to $to.
     *
     * @param mixed $data Data to repair
     * @param string $to Target encoding (UTF-8, ISO, etc.)
     * @param string $from The "glitch" encoding (usually ISO/Windows-1252) that caused the double encoding.
     * @param array<string,mixed> $options Conversion options
     *                                     - 'normalize': bool (default: true)
     *                                     - 'translit': bool (default: true)
     *                                     - 'ignore': bool (default: true)
     *                                     - 'maxDepth' : int (default: 5)
     *
     * @return mixed
     *
     * @throws InvalidArgumentException If encoding is invalid
     */
    public static function repair(
        $data,
        string $to = self::ENCODING_UTF8,
        string $from = self::ENCODING_ISO,
        array $options = []
    ) {
        $options = self::configureOptions(
            $options,
            ['maxDepth' => self::MAX_REPAIR_DEPTH]
        );

        /**
         * @psalm-suppress MissingClosureParamType
         * @psalm-suppress MissingClosureReturnType
         */
        $callback = static fn ($value) => self::repairValue($value, $to, $from, $options);

        return self::applyRecursive($data, $callback);
    }

    /**
     * Safe JSON encoding to ensure UTF-8 compliance.
     *
     * @param mixed $data
     * @param int $flags JSON encode flags
     * @param int<1, 2147483647> $depth Maximum depth
     * @param string $from Source encoding for repair
     *
     * @return string JSON UTF-8 string
     *
     * @throws RuntimeException if error occured.
     */
    public static function safeJsonEncode(
        $data,
        int $flags = 0,
        int $depth = self::JSON_DEFAULT_DEPTH,
        string $from = self::WINDOWS_1252
    ): string {
        /** @var mixed $data */
        $data = self::repair($data, self::ENCODING_UTF8, $from);
        /** @var string|false $json */
        $json = \json_encode($data, $flags, $depth);

        if (false === $json) {
            throw new RuntimeException(
                'JSON Encode Error: ' . \json_last_error_msg()
            );
        }

        return $json;
    }

    /**
     * Safe JSON decoding with charset conversion.
     *
     * @param string $json JSON string
     * @param bool|null $associative Return associative array
     * @param int<1, 2147483647> $depth Maximum depth
     * @param int $flags JSON decode flags
     * @param string $to Target encoding
     * @param string $from Source encoding for repair
     *
     * @return mixed Decoded data
     *
     * @throws RuntimeException If decoding fails
     */
    public static function safeJsonDecode(
        string $json,
        ?bool $associative = null,
        int $depth = self::JSON_DEFAULT_DEPTH,
        int $flags = 0,
        string $to = self::ENCODING_UTF8,
        string $from = self::WINDOWS_1252
    ) {
        // Repair string to a valid UTF-8 for decoding
        /** @var string $data */
        $data = self::repair($json, self::ENCODING_UTF8, $from);
        /** @var mixed $result */
        $result = \json_decode($data, $associative, $depth, $flags);

        if (null === $result && \JSON_ERROR_NONE !== \json_last_error()) {
            throw new RuntimeException(
                'JSON Decode Error: ' . \json_last_error_msg()
            );
        }

        return self::toCharset($result, $to, self::ENCODING_UTF8);
    }

    /**
     * Applies a callback recursively to arrays, objects, and scalar values.
     *
     * @param mixed $data Data to process
     * @param callable $callback Processing callback function
     *
     * @return mixed
     */
    private static function applyRecursive($data, callable $callback)
    {
        if (\is_array($data)) {
            return \array_map(
                /**
                 * @psalm-suppress MissingClosureReturnType
                 * @psalm-suppress MissingClosureParamType
                 */
                static fn ($item) => self::applyRecursive($item, $callback),
                $data
            );
        }

        if (\is_object($data)) {
            return self::applyToObject($data, $callback);
        }

        // Apply the transformation on scalar value
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
    private static function applyToObject(object $data, callable $callback): object
    {
        $copy = clone $data;

        $properties = \get_object_vars($copy);
        /** @var mixed $value */
        foreach ($properties as $key => $value) {
            $copy->$key = self::applyRecursive($value, $callback);
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
    private static function convertValue(
        $value,
        string $to,
        string $from,
        array $options
    ) {
        if (!\is_string($value)) {
            return $value;
        }

        // Special handling when converting FROM UTF-8
        // Do not trust mbstring when return utf-8 but we want another encoding,
        // because it will return true even if it's not really valid.
        if (self::ENCODING_UTF8 !== $to && self::isValidUtf8($value)) {
            return self::convertString($value, $to, self::ENCODING_UTF8, $options);
        }

        // Check if already in target encoding
        if (\mb_check_encoding($value, $to)) {
            return self::normalize($value, $to, $options);
        }

        return self::convertString($value, $to, $from, $options);
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
    private static function convertString(
        string $data,
        string $to,
        string $from,
        array $options
    ): string {
        // Return original if everything failed
        return self::transcodeString($data, $to, $from, $options) ?? $data;
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
    private static function transcodeString(
        string $data,
        string $to,
        string $from,
        array $options
    ): ?string {
        $targetEncoding = self::resolveEncoding($to, $data, $options);
        $sourceEncoding = self::resolveEncoding($from, $data, $options);

        $result = self::getTranscoderChain()->transcode(
            $data,
            $targetEncoding,
            $sourceEncoding,
            $options
        );

        if (null !== $result && self::ENCODING_UTF8 === $targetEncoding) {
            return self::normalize($result, $targetEncoding, $options);
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
    private static function repairValue(
        $value,
        string $to,
        string $from,
        array $options
    ) {
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

        $fixed = self::peelEncodingLayers($value, $from, $maxDepth);
        $detectedEncoding = self::isValidUtf8($fixed) ? self::ENCODING_UTF8 : $from;

        return self::toCharset($fixed, $to, $detectedEncoding, $options);
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
    private static function peelEncodingLayers(
        string $value,
        string $from,
        int $maxDepth
    ): string {
        $fixed = $value;
        $iterations = 0;
        $options = [
            'normalize' => false,
            'translit' => false,
            'ignore' => false,
        ];

        // Loop while it looks like valid UTF-8
        while ($iterations < $maxDepth && self::isValidUtf8($fixed)) {
            // Attempt to reverse convert (UTF-8 -> $from)
            $test = self::transcodeString($fixed, $from, self::ENCODING_UTF8, $options);

            if (null === $test || $test === $fixed || !self::isValidUtf8($test)) {
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
     */
    private static function resolveEncoding(
        string $encoding,
        string $data,
        array $options
    ): string {
        return self::AUTO === $encoding
            // @codeCoverageIgnoreStart
            ? self::detect($data, $options)
            // @codeCoverageIgnoreEnd
            : $encoding;
    }

    /**
     * Normalizes UTF-8 string if needed.
     *
     * @param string $value String to normalize
     * @param string $to Target encoding
     * @param array<string, mixed> $options Configuration
     *
     * @return string Normalized or original string
     */
    private static function normalize(
        string $value,
        string $to,
        array $options
    ): string {
        if (self::ENCODING_UTF8 !== $to || false !== ($options['normalize'] ?? true)) {
            return $value;
        }

        // @codeCoverageIgnoreStart
        if (!\class_exists(Normalizer::class)) {
            return $value;
        }
        // @codeCoverageIgnoreEnd

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
    private static function isValidUtf8(string $string): bool
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
    private static function validateEncoding(string $encoding, string $type): void
    {
        $normalized = \strtoupper($encoding);

        if (
            !\in_array($encoding, self::ALLOWED_ENCODINGS, true)
            && !\in_array($normalized, self::ALLOWED_ENCODINGS, true)
        ) {
            throw new InvalidArgumentException(
                \sprintf(
                    'Invalid %s encoding: "%s". Allowed: %s',
                    $type,
                    $encoding,
                    \implode(', ', self::ALLOWED_ENCODINGS)
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
    private static function configureOptions(array $options, array ...$replacements): array
    {
        $replacements[] = $options;

        return \array_replace(
            [
                'normalize' => true,
                'translit' => true,
                'ignore' => true,
                'encodings' => self::DEFAULT_ENCODINGS,
            ],
            ...$replacements
        );
    }
}
