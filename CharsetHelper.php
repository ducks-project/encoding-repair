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

use Ducks\Component\EncodingRepair\Detector\CallableDetector;
use Ducks\Component\EncodingRepair\Detector\DetectorInterface;
use Ducks\Component\EncodingRepair\Transcoder\CallableTranscoder;
use Ducks\Component\EncodingRepair\Transcoder\TranscoderInterface;
use InvalidArgumentException;
use JsonException;

/**
 * Static facade for charset processing.
 *
 * @psalm-api
 *
 * @final
 */
final class CharsetHelper
{
    public const AUTO = CharsetProcessorInterface::AUTO;
    public const WINDOWS_1252 = CharsetProcessorInterface::WINDOWS_1252;
    public const ENCODING_ISO = CharsetProcessorInterface::ENCODING_ISO;
    public const ENCODING_UTF8 = CharsetProcessorInterface::ENCODING_UTF8;
    public const ENCODING_UTF16 = CharsetProcessorInterface::ENCODING_UTF16;
    public const ENCODING_UTF32 = CharsetProcessorInterface::ENCODING_UTF32;
    public const ENCODING_ASCII = CharsetProcessorInterface::ENCODING_ASCII;

    /**
     * @var CharsetProcessorInterface|null
     */
    private static $processor = null;

    /**
     * @psalm-api
     *
     * @codeCoverageIgnore
     */
    private function __construct()
    {
    }

    /**
     * Get or initialize processor.
     *
     * @return CharsetProcessorInterface
     */
    private static function getProcessor(): CharsetProcessorInterface
    {
        if (null === self::$processor) {
            self::$processor = new CharsetProcessor();
        }

        return self::$processor;
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
    public static function registerTranscoder($transcoder, ?int $priority = null): void
    {
        /** @var mixed $transcoder */
        if ($transcoder instanceof TranscoderInterface) {
            // @codeCoverageIgnoreStart
            self::getProcessor()->registerTranscoder($transcoder, $priority);
            return;
            // @codeCoverageIgnoreEnd
        }

        if (\is_callable($transcoder)) {
            /** @var callable(string, string, string, null|array<string, mixed>): (string|null) $transcoder */
            $wrapper = new CallableTranscoder($transcoder, $priority ?? 0);
            self::getProcessor()->registerTranscoder($wrapper, $priority);
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
    public static function registerDetector($detector, ?int $priority = null): void
    {
        /** @var mixed $detector */
        if ($detector instanceof DetectorInterface) {
            // @codeCoverageIgnoreStart
            self::getProcessor()->registerDetector($detector, $priority);
            return;
            // @codeCoverageIgnoreEnd
        }

        if (\is_callable($detector)) {
            /** @var callable(string, array<string, mixed>|null): (string|null) $detector */
            $wrapper = new CallableDetector($detector, $priority ?? 0);
            self::getProcessor()->registerDetector($wrapper, $priority);
            return;
        }

        throw new InvalidArgumentException(
            'Detector must be an instance of DetectorInterface or a callable'
        );
    }

    /**
     * Checks if a string is encoded in the specified encoding.
     *
     * @param string $string String to check
     * @param string $encoding Expected encoding
     * @param array<string, mixed> $options Detection options
     *                                      - 'encodings': array of encodings to test
     *
     * @return bool True if string matches the encoding
     *
     * @throws InvalidArgumentException If encoding is invalid
     */
    public static function is(string $string, string $encoding, array $options = []): bool
    {
        return self::getProcessor()->is($string, $encoding, $options);
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
        return self::getProcessor()->detect($string, $options);
    }

    /**
     * Batch detects the charset encoding of iterable items.
     *
     * @param iterable<mixed> $items items to loop for analyzis
     * @param array<string, mixed> $options Conversion options
     *                                      - 'encodings': array of encodings to test
     *                                      - 'maxSamples': int number of samples to test (default: 1)
     *
     * @return string Detected encoding (uppercase)
     */
    public static function detectBatch(iterable $items, array $options = []): string
    {
        return self::getProcessor()->detectBatch($items, $options);
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
        return self::getProcessor()->toCharset($data, $to, $from, $options);
    }

    /**
     * Batch convert array items from one encoding to another.
     *
     * @param array<mixed> $items Items to convert
     * @param string $to Target encoding
     * @param string $from Source encoding (use AUTO for detection)
     * @param array<string, mixed> $options Conversion options
     *
     * @return array<mixed> Converted items
     *
     * @throws InvalidArgumentException If encoding is invalid
     */
    public static function toCharsetBatch(
        array $items,
        string $to = self::ENCODING_UTF8,
        string $from = self::ENCODING_ISO,
        array $options = []
    ): array {
        return self::getProcessor()->toCharsetBatch($items, $to, $from, $options);
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
    public static function toUtf8($data, string $from = self::WINDOWS_1252, array $options = [])
    {
        return self::getProcessor()->toCharset($data, self::ENCODING_UTF8, $from, $options);
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
    public static function toIso($data, string $from = self::ENCODING_UTF8, array $options = [])
    {
        return self::getProcessor()->toCharset($data, self::WINDOWS_1252, $from, $options);
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
        return self::getProcessor()->repair($data, $to, $from, $options);
    }

    /**
     * Safe JSON encoding to ensure UTF-8 compliance.
     *
     * Note: JSON_THROW_ON_ERROR flag is automatically added to $flags.
     *
     * @param mixed $data
     * @param int $flags JSON encode flags (JSON_THROW_ON_ERROR is automatically added)
     * @param int<1, 2147483647> $depth Maximum depth
     * @param string $from Source encoding for repair
     *
     * @return string JSON UTF-8 string
     *
     * @throws JsonException If JSON encoding fails
     */
    public static function safeJsonEncode(
        $data,
        int $flags = 0,
        int $depth = 512,
        string $from = self::WINDOWS_1252
    ): string {
        return self::getProcessor()->safeJsonEncode($data, $flags, $depth, $from);
    }

    /**
     * Safe JSON decoding with charset conversion.
     *
     * Note: JSON_THROW_ON_ERROR flag is automatically added to $flags.
     *
     * @param string $json JSON string
     * @param bool|null $associative Return associative array
     * @param int<1, 2147483647> $depth Maximum depth
     * @param int $flags JSON decode flags (JSON_THROW_ON_ERROR is automatically added)
     * @param string $to Target encoding
     * @param string $from Source encoding for repair
     *
     * @return mixed Decoded data
     *
     * @throws JsonException If JSON decoding fails
     */
    public static function safeJsonDecode(
        string $json,
        ?bool $associative = null,
        int $depth = 512,
        int $flags = 0,
        string $to = self::ENCODING_UTF8,
        string $from = self::WINDOWS_1252
    ) {
        return self::getProcessor()->safeJsonDecode($json, $associative, $depth, $flags, $to, $from);
    }
}
