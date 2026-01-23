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

use Ducks\Component\EncodingRepair\Detector\DetectorInterface;
use Ducks\Component\EncodingRepair\Interpreter\PropertyMapperInterface;
use Ducks\Component\EncodingRepair\Interpreter\TypeInterpreterInterface;
use Ducks\Component\EncodingRepair\Transcoder\TranscoderInterface;
use InvalidArgumentException;
use JsonException;

/**
 * Interface for charset processing service.
 *
 * @psalm-api
 */
interface CharsetProcessorInterface
{
    public const AUTO = 'AUTO';
    public const WINDOWS_1252 = 'CP1252';
    public const ENCODING_ISO = 'ISO-8859-1';
    public const ENCODING_UTF8 = 'UTF-8';
    public const ENCODING_UTF16 = 'UTF-16';
    public const ENCODING_UTF32 = 'UTF-32';
    public const ENCODING_ASCII = 'ASCII';

    /**
     * Register a transcoder with optional priority.
     *
     * @param TranscoderInterface $transcoder Transcoder instance
     * @param int|null $priority Priority override (null = use transcoder's default)
     *
     * @return self
     */
    public function registerTranscoder(TranscoderInterface $transcoder, ?int $priority = null): self;

    /**
     * Unregister a transcoder.
     *
     * @param TranscoderInterface $transcoder
     *
     * @return self
     */
    public function unregisterTranscoder(TranscoderInterface $transcoder): self;

    /**
     * Queue multiple transcoders at once.
     *
     * @param TranscoderInterface ...$transcoders Transcoder instances
     *
     * @return self
     */
    public function queueTranscoders(TranscoderInterface ...$transcoders): self;

    /**
     * Reset all transcoders to defaults.
     *
     * @return self
     */
    public function resetTranscoders(): self;

    /**
     * Register a detector with optional priority.
     *
     * @param DetectorInterface $detector Detector instance
     * @param int|null $priority Priority override (null = use transcoder's default)
     *
     * @return self
     */
    public function registerDetector(DetectorInterface $detector, ?int $priority = null): self;

    /**
     * Unregister a detector.
     *
     * @param DetectorInterface $detector
     *
     * @return self
     */
    public function unregisterDetector(DetectorInterface $detector): self;

    /**
     * Queue multiple detectors at once.
     *
     * @param DetectorInterface ...$detectors Detector instances
     *
     * @return self
     */
    public function queueDetectors(DetectorInterface ...$detectors): self;

    /**
     * Reset all detectors to defaults.
     *
     * @return self
     */
    public function resetDetectors(): self;

    /**
     * Add allowed encodings.
     *
     * @param string ...$encodings encodings name
     *
     * @return self
     */
    public function addEncodings(string ...$encodings): self;

    /**
     * Remove allowed encodings.
     *
     * @param string ...$encodings encodings name
     *
     * @return self
     */
    public function removeEncodings(string ...$encodings): self;

    /**
     * Get allowed encodings.
     *
     * @return list<string>
     */
    public function getEncodings(): array;

    /**
     * Reset encodings to defaults.
     *
     * @return self
     */
    public function resetEncodings(): self;

    /**
     * Register a type interpreter with optional priority.
     *
     * @param TypeInterpreterInterface $interpreter Interpreter to register
     * @param int|null $priority Priority override (null = use interpreter's default)
     *
     * @return self
     */
    public function registerInterpreter(TypeInterpreterInterface $interpreter, ?int $priority = null): self;

    /**
     * Unregister a type interpreter from the chain.
     *
     * @param TypeInterpreterInterface $interpreter Interpreter to remove
     *
     * @return self
     */
    public function unregisterInterpreter(TypeInterpreterInterface $interpreter): self;

    /**
     * Register a property mapper for a specific class.
     *
     * @param string $className Fully qualified class name
     * @param PropertyMapperInterface $mapper Property mapper instance
     *
     * @return self
     */
    public function registerPropertyMapper(string $className, PropertyMapperInterface $mapper): self;

    /**
     * Reset interpreters to default configuration.
     *
     * @return self
     */
    public function resetInterpreters(): self;

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
    public function toCharset(
        $data,
        string $to = self::ENCODING_UTF8,
        string $from = self::ENCODING_ISO,
        array $options = []
    );

    /**
     * Batch convert iterable items from one encoding to another.
     *
     * Optimized for homogeneous arrays: detects encoding once on first non-empty string.
     * Use this instead of toCharset() when processing large arrays with AUTO detection.
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
    public function toCharsetBatch(
        array $items,
        string $to = self::ENCODING_UTF8,
        string $from = self::ENCODING_ISO,
        array $options = []
    ): array;

    /**
     * Detects the charset encoding of a string.
     *
     * @param string $string String to analyze
     * @param array<string, mixed> $options Conversion options
     *                                      - 'encodings': array of encodings to test
     *
     * @return string Detected encoding (uppercase)
     */
    public function detect(string $string, array $options = []): string;

    /**
     * Batch detects the charset encoding of iterable items.
     *
     * @param iterable<mixed> $items items to loop for analyzis
     * @param array<string, mixed> $options Conversion options
     *                                      - 'encodings': array of encodings to test
     *
     * @return string Detected encoding (uppercase)
     */
    public function detectBatch(iterable $items, array $options = []): string;

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
    public function repair(
        $data,
        string $to = self::ENCODING_UTF8,
        string $from = self::ENCODING_ISO,
        array $options = []
    );

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
    public function safeJsonEncode(
        $data,
        int $flags = 0,
        int $depth = 512,
        string $from = self::WINDOWS_1252
    ): string;

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
    public function safeJsonDecode(
        string $json,
        ?bool $associative = null,
        int $depth = 512,
        int $flags = 0,
        string $to = self::ENCODING_UTF8,
        string $from = self::WINDOWS_1252
    );
}
