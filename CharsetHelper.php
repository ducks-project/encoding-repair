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

use finfo;
use InvalidArgumentException;
use Normalizer;
use RuntimeException;
use UConverter;

/**
 * Helper class for encoding and detect charset
 *
 * Designed to handle legacy ISO-8859-1 <-> UTF-8 interoperability issues.
 * Implements Chain of Responsibility pattern for extensibility.
 *
 * @psalm-api
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
     * List of internal transcoders (Providers) by priority.
     * Can be extended in the future.
     *
     * @var list<string|callable(string, string, string, array<string, mixed>): (string|null)>
     */
    private static $transcoders = [
        // Priority 1: Best precision (Intl)
        'transcodeWithUConverter',
        // Priority 2: Good performance (Sys)
        'transcodeWithIconv',
        // Priority 3: Fail-safe (Permissive)
        'transcodeWithMbString',
    ];

    /**
     * List of internal detectors (Providers) by priority.
     *
     * @var list<string|callable(string, array<string, mixed>): (string|null)>
     */
    private static $detectors = [
        'detectWithMbString',
        'detectWithFileInfo',
    ];

    /**
     * Private constructor to prevent instantiation of static utility class.
     *
     * @psalm-api
     */
    private function __construct()
    {
    }

    /**
     * Allow registering a custom provider/transcoder in the future.
     *
     * @param string|callable(string, string, string, array<string, mixed>): (string|null) $transcoder
     *   Method name or callable with signature: fn (string, string, string, array): string|null
     * @param bool $prepend Priority (Top of the list)
     *
     * @return void
     *
     * @throws InvalidArgumentException If transcoder is invalid
     */
    public static function registerTranscoder(
        $transcoder,
        bool $prepend = true
    ): void {
        self::validateTranscoder($transcoder);

        if ($prepend) {
            \array_unshift(self::$transcoders, $transcoder);
        } else {
            self::$transcoders[] = $transcoder;
        }
    }

    /**
     * Register a custom detector provider.
     *
     * @param string|callable(string, array<string, mixed>): (string|null) $detector
     *   Method name or callable with signature: fn (string, string, string, array): string|null
     * @param bool $prepend Priority (Top of the list)
     *
     * @return void
     *
     * @throws InvalidArgumentException If detector is invalid
     */
    public static function registerDetector(
        $detector,
        bool $prepend = true
    ): void {
        self::validateDetector($detector);

        if ($prepend) {
            \array_unshift(self::$detectors, $detector);
        } else {
            self::$detectors[] = $detector;
        }
    }

    /**
     * Detects the charset encoding of a string.
     *
     * @param string $string String to analyze
     * @param array<string, mixed> $options Conversion options
     * - 'encodings': array of encodings to test
     *
     * @return string Detected encoding (uppercase)
     */
    public static function detect(string $string, array $options = []): string
    {
        // Fast common return.
        if (self::isValidUtf8($string)) {
            return self::ENCODING_UTF8;
        }

        // Loop over registered detectors
        foreach (self::$detectors as $detector) {
            try {
                $args = [$string, $options];
                $detected = self::invokeProvider($detector, ...$args);
            } catch (\Throwable $th) {
                continue;
            }

            if (null !== $detected) {
                return $detected;
            }
        }

        return self::ENCODING_ISO;
    }

    /**
     * Convert $data string from one encoding to another.
     *
     * @param mixed $data Data to convert
     * @param string $to Target encoding
     * @param string $from Source encoding (use AUTO for detection)
     * @param array<string, mixed> $options Conversion options
     * - 'normalize': bool (default: true)
     * - 'translit': bool (default: true)
     * - 'ignore': bool (default: true)
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
     * - 'normalize': bool (default: true)
     * - 'translit': bool (default: true)
     * - 'ignore': bool (default: true)
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
     * - 'normalize': bool (default: true)
     * - 'translit': bool (default: true)
     * - 'ignore': bool (default: true)
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
     * - 'normalize': bool (default: true)
     * - 'translit': bool (default: true)
     * - 'ignore': bool (default: true)
     * - 'maxDepth' : int (default: 5)
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
     * @throws \RuntimeException If decoding fails
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

        // Loop over the static providers list (Chain of Responsibility)
        foreach (self::$transcoders as $transcoder) {
            try {
                // Note: In the future, we use external classes,
                // check if $method is callable or an instance of an interface.
                // For now, we assume internal static methods.
                $result = self::invokeProvider(
                    $transcoder,
                    $data,
                    $targetEncoding,
                    $sourceEncoding,
                    $options
                );
            } catch (\Throwable $th) {
                continue;
            }

            if (null !== $result) {
                if (self::ENCODING_UTF8 === $targetEncoding) {
                    return self::normalize($result, $targetEncoding, $options);
                }
                return $result;
            }
        }

        return null;
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
            return $value;
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
            ? self::detect($data, $options)
            : $encoding;
    }

    // phpcs:disable Generic.Files.LineLength.TooLong
    /**
     * Invokes a provider (method name or callable) with given arguments.
     *
     * @param string|callable(string, string, string, array<string, mixed>): (string|null)|callable(string, array<string, mixed>): (string|null) $provider Provider to call (method name or callable)
     * @param array<mixed>|string $args Arguments to pass to the provider
     *
     * @return string|null Result of the provider call
     *
     * @throws InvalidArgumentException when provider is not callable.
     *
     * @psalm-param array<string, mixed>|string $args
     */
    // phpcs:enable Generic.Files.LineLength.TooLong
    private static function invokeProvider($provider, ...$args)
    {
        /** @var mixed $result */
        $result = null;

        if (\is_string($provider) && \method_exists(self::class, $provider)) {
            /** @var mixed $result */
            $result = self::$provider(...$args);
        } elseif (\is_callable($provider)) {
            /**
             * @psalm-suppress InvalidArgument
             * @psalm-suppress MixedArgument
             */
            $result = $provider(...$args);
        }

        if (null !== $result && !\is_string($result)) {
            throw new InvalidArgumentException('Provider is not callable');
        }

        return $result;
    }

    /**
     * Validates a provider before registration.
     *
     * @param mixed $provider Provider to validate
     * @param string $type Type name for error message
     *
     * @throws InvalidArgumentException If provider is invalid
     */
    private static function validateProvider($provider, string $type): void
    {
        if (!\is_string($provider) && !\is_callable($provider)) {
            throw new InvalidArgumentException(
                \sprintf(
                    '%s must be a string (method name) or callable',
                    $type
                )
            );
        }

        if (\is_string($provider) && !\method_exists(self::class, $provider)) {
            throw new InvalidArgumentException(
                \sprintf(
                    'Method "%s" does not exist in %s',
                    $provider,
                    self::class
                )
            );
        }
    }

    /**
     * Validates a transcoder before registration.
     *
     * @param mixed $transcoder Transcoder to validate
     *
     * @throws InvalidArgumentException If invalid
     */
    private static function validateTranscoder($transcoder): void
    {
        self::validateProvider($transcoder, 'Transcoder');
    }

    /**
     * Validates a detector before registration.
     *
     * @param mixed $detector Detector to validate
     *
     * @throws InvalidArgumentException If invalid
     */
    private static function validateDetector($detector): void
    {
        self::validateProvider($detector, 'Detector');
    }

    /**
     * Attempts conversion using UConverter extension.
     *
     * @param string $data String to convert
     * @param string $to Target encoding
     * @param string $from Source encoding,
     * @param array<string, mixed> $options Options
     *
     * @return string|null Converted string or null on failure
     *
     * @psalm-api
     */
    private static function transcodeWithUConverter(
        string $data,
        string $to,
        string $from,
        array $options
    ) {
        if (!\class_exists(UConverter::class)) {
            return null;
        }

        $intlOptions = \array_intersect_key(
            $options,
            ['to_subst' => true]
        ) ?: null;

        /** @var string|false $result */
        // @phpstan-ignore argument.type
        $result = UConverter::transcode($data, $to, $from, $intlOptions);

        return false !== $result ? $result : null;
    }

    /**
     * Attempts transcode using iconv.
     *
     * @param string $data String to convert
     * @param string $to Target encoding
     * @param string $from Source encoding
     * @param array<string, mixed> $options Options
     *
     * @return string|null Converted string or null on failure
     *
     * @psalm-api
     */
    private static function transcodeWithIconv(
        string $data,
        string $to,
        string $from,
        array $options
    ) {
        if (!\function_exists('iconv')) {
            return null;
        }

        $suffix = self::buildIconvSuffix($options);

        // Use silence operator (@) instead of
        // \set_error_handler(static fn (): bool => true);
        // set_error_handler is too expensive for high-volume loops.
        $result = @\iconv($from, $to . $suffix, $data);

        return false !== $result ? $result : null;
    }

    /**
     * Builds iconv suffix from configuration.
     *
     * @param array<string, mixed> $options Configuration array
     *
     * @return string Suffix string (e.g., '//TRANSLIT//IGNORE')
     */
    private static function buildIconvSuffix(array $options): string
    {
        $parts = '';

        if (true === ($options['translit'] ?? true)) {
            $parts .= '//TRANSLIT';
        }

        if (true === ($options['ignore'] ?? true)) {
            $parts .= '//IGNORE';
        }

        return $parts;
    }

    /**
     * Attempts transcode using mb_string.
     *
     * @param string $data String to convert
     * @param string $to Target encoding
     * @param string $from Source encoding
     * @param array<string, mixed> $_options Options (unused by mbstring)
     *
     * @return string|null Converted string or null on failure
     *
     * @psalm-api
     */
    private static function transcodeWithMbString(
        string $data,
        string $to,
        string $from,
        array $_options
    ) {
        if (!\function_exists('mb_convert_encoding')) {
            return null;
        }

        $result = \mb_convert_encoding($data, $to, $from);

        /** @var string|false $result */
        return false !== $result ? $result : null;
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

        if (!\class_exists(Normalizer::class)) {
            return $value;
        }

        $normalized = Normalizer::normalize($value);

        return false !== $normalized ? $normalized : $value;
    }

    /**
     * Detects encoding using mbstring extension.
     *
     * @param string $string String to analyze
     * @param array<string, mixed> $options Options usable by mb_string
     * - encodings : A list of character encodings to try
     *
     * @return string|null Detected encoding or null
     *
     * @psalm-api
     */
    private static function detectWithMbString(string $string, array $options = []): ?string
    {
        /** @var mixed|list<string> */
        $encodings = $options['encodings'] ?? self::DEFAULT_ENCODINGS;

        if (!\is_array($encodings)) {
            $encodings = self::DEFAULT_ENCODINGS;
        }

        $detected = \mb_detect_encoding($string, $encodings, true);

        return false !== $detected ? $detected : null;
    }

    /**
     * Detects encoding using FileInfo extension.
     *
     * @param string $string String to analyze
     * @param array<string, mixed> $options Options usable by finfo
     *
     * @return string|null Detected encoding or null
     *
     * @psalm-api
     */
    private static function detectWithFileInfo(string $string, array $options = []): ?string
    {
        if (!\class_exists(finfo::class)) {
            return null;
        }

        // use an array in order to pass args througt functions
        // in order to ensure several php compatibility.
        $args = [];

        /** @var mixed|string|null $magic */
        $magic = $options['finfo_magic'] ?? null;
        if (\is_string($magic)) {
            $args[] = $magic;
        }

        $finfo = new finfo(FILEINFO_MIME_ENCODING, ...$args);

        $args = [];

        /** @var mixed|int */
        $flags = $options['finfo_flags'] ?? \FILEINFO_NONE;
        if (!\is_int($flags)) {
            $flags = \FILEINFO_NONE;
        }
        $args[] = $flags;

        /** @var mixed|resource|null */
        $context = $options['finfo_context'] ?? null;
        if (\is_resource($context)) {
            $args[] = $context;
        }

        $detected = $finfo->buffer(
            $string,
            ...$args
        );

        if (false === $detected || 'binary' === $detected) {
            return null;
        }

        // Returns things like 'iso-8859-1', we uppercase it
        return \strtoupper($detected);
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
