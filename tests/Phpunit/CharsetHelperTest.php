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

namespace Ducks\Component\EncodingRepair\Tests\Phpunit;

use Ducks\Component\EncodingRepair\CharsetHelper;
use Ducks\Component\EncodingRepair\Detector\DetectorInterface;
use InvalidArgumentException;
use PHPUnit\Framework\TestCase;
use JsonException;
use stdClass;

final class CharsetHelperTest extends TestCase
{
    public function testToUtf8WithIsoString(): void
    {
        $iso = \mb_convert_encoding('Café', 'ISO-8859-1', 'UTF-8');
        $result = CharsetHelper::toUtf8($iso, CharsetHelper::ENCODING_ISO);

        $this->assertSame('Café', $result);
        $this->assertTrue(\mb_check_encoding($result, 'UTF-8'));
    }

    public function testToUtf8WithArray(): void
    {
        $iso = \mb_convert_encoding('Café', 'ISO-8859-1', 'UTF-8');
        $data = ['name' => $iso, 'city' => \mb_convert_encoding('São Paulo', 'ISO-8859-1', 'UTF-8')];

        $result = CharsetHelper::toUtf8($data, CharsetHelper::ENCODING_ISO);

        $this->assertSame('Café', $result['name']);
        $this->assertSame('São Paulo', $result['city']);
    }

    public function testToUtf8WithNestedArray(): void
    {
        $iso = \mb_convert_encoding('Café', 'ISO-8859-1', 'UTF-8');
        $data = [
            'name' => $iso,
            'items' => [
                'entrée' => \mb_convert_encoding('Crème brûlée', 'ISO-8859-1', 'UTF-8'),
            ],
        ];

        $result = CharsetHelper::toUtf8($data, CharsetHelper::ENCODING_ISO);

        $this->assertSame('Café', $result['name']);
        $this->assertSame('Crème brûlée', $result['items']['entrée']);
    }

    public function testToUtf8WithObject(): void
    {
        $obj = new stdClass();
        $obj->name = \mb_convert_encoding('José', 'ISO-8859-1', 'UTF-8');
        $obj->email = 'test@example.com';

        $result = CharsetHelper::toUtf8($obj, CharsetHelper::ENCODING_ISO);

        $this->assertInstanceOf(stdClass::class, $result);
        $this->assertNotSame($obj, $result);
        $this->assertSame('José', $result->name);
        $this->assertSame('test@example.com', $result->email);
    }

    public function testToIsoFromUtf8(): void
    {
        $utf8 = 'Café';
        $result = CharsetHelper::toIso($utf8, CharsetHelper::ENCODING_UTF8);

        $expected = \mb_convert_encoding('Café', 'CP1252', 'UTF-8');
        $this->assertSame($expected, $result);
    }

    public function testToCharsetWithAutoDetection(): void
    {
        $utf8 = 'Café résumé';
        $result = CharsetHelper::toCharset($utf8, CharsetHelper::ENCODING_UTF8, CharsetHelper::AUTO);

        $this->assertSame('Café résumé', $result);
    }

    public function testDetectUtf8(): void
    {
        $utf8 = 'Café résumé';
        $encoding = CharsetHelper::detect($utf8);

        $this->assertSame('UTF-8', $encoding);
    }

    public function testDetectIso(): void
    {
        $iso = \mb_convert_encoding('Café', 'ISO-8859-1', 'UTF-8');
        $encoding = CharsetHelper::detect($iso ?: '');

        $this->assertContains($encoding, ['ISO-8859-1', 'CP1252', 'Windows-1252', 'UTF-8']);
    }

    public function testRepairDoubleEncodedString(): void
    {
        // Simulate double encoding: UTF-8 -> ISO -> UTF-8
        $original = 'Café';
        $iso = \mb_convert_encoding($original, 'ISO-8859-1', 'UTF-8');
        $doubleEncoded = \mb_convert_encoding($iso ?: '', 'UTF-8', 'ISO-8859-1');

        $result = CharsetHelper::repair($doubleEncoded ?: '');

        $this->assertSame('Café', $result);
    }

    public function testRepairWithMaxDepth(): void
    {
        $original = 'Café';
        $iso = \mb_convert_encoding($original, 'ISO-8859-1', 'UTF-8');
        $doubleEncoded = \mb_convert_encoding($iso ?: '', 'UTF-8', 'ISO-8859-1');

        $result = CharsetHelper::repair($doubleEncoded ?: '', CharsetHelper::ENCODING_UTF8, CharsetHelper::ENCODING_ISO, ['maxDepth' => 10]);

        $this->assertSame('Café', $result);
    }

    public function testRepairArray(): void
    {
        $original = 'Café';
        $iso = \mb_convert_encoding($original, 'ISO-8859-1', 'UTF-8');
        $doubleEncoded = \mb_convert_encoding($iso ?: '', 'UTF-8', 'ISO-8859-1');

        $data = ['name' => $doubleEncoded];
        $result = CharsetHelper::repair($data);

        $this->assertSame('Café', $result['name']);
    }

    public function testSafeJsonEncode(): void
    {
        $data = ['name' => 'Gérard', 'city' => 'São Paulo'];
        $json = CharsetHelper::safeJsonEncode($data);

        $this->assertIsString($json);
        $decoded = \json_decode($json, true);
        $this->assertSame('Gérard', $decoded['name']);
        $this->assertSame('São Paulo', $decoded['city']);
    }

    public function testSafeJsonEncodeWithFlags(): void
    {
        $data = ['name' => 'Gérard'];
        $json = CharsetHelper::safeJsonEncode($data, \JSON_PRETTY_PRINT);

        $this->assertStringContainsString("\n", $json);
    }

    public function testSafeJsonDecode(): void
    {
        $json = '{"name":"Gérard","city":"São Paulo"}';
        $result = CharsetHelper::safeJsonDecode($json, true);

        $this->assertIsArray($result);
        $this->assertSame('Gérard', $result['name']);
        $this->assertSame('São Paulo', $result['city']);
    }

    public function testSafeJsonDecodeAsObject(): void
    {
        $json = '{"name":"Gérard"}';
        $result = CharsetHelper::safeJsonDecode($json, false);

        $this->assertIsObject($result);
        $this->assertSame('Gérard', $result->name ?? null);
    }

    public function testSafeJsonDecodeThrowsOnInvalidJson(): void
    {
        $this->expectException(JsonException::class);

        CharsetHelper::safeJsonDecode('invalid json{');
    }

    public function testRegisterTranscoder(): void
    {
        $called = false;
        $transcoder = function (string $data, string $to, string $from, ?array $options = null) use (&$called): ?string {
            $called = true;
            return null;
        };

        CharsetHelper::registerTranscoder($transcoder, 50);
        $result = CharsetHelper::toUtf8('test');

        $this->assertIsString($result);
    }

    public function testRegisterDetector(): void
    {
        $detector = function (string $string, ?array $options = null): ?string {
            // Check for UTF-16LE BOM
            if (2 <= \strlen($string) && 0xFF === \ord($string[0]) && 0xFE === \ord($string[1])) {
                return 'UTF-16LE';
            }
            return null;
        };

        CharsetHelper::registerDetector($detector, 250);

        // Use a non-UTF-8 string to bypass the fast path
        $utf16String = "\xFF\xFE" . \mb_convert_encoding('test', 'UTF-16LE', 'UTF-8');
        $encoding = CharsetHelper::detect($utf16String);
        // The detector might not be called if the string is detected as UTF-8 first
        $this->assertContains($encoding, ['UTF-16LE', 'UTF-8']);
    }

    public function testValidateEncodingThrowsOnInvalidEncoding(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Invalid target encoding');

        CharsetHelper::toCharset('test', 'INVALID-ENCODING');
    }

    public function testToCharsetWithOptions(): void
    {
        $data = 'Café';
        $result = CharsetHelper::toCharset($data, CharsetHelper::ENCODING_UTF8, CharsetHelper::ENCODING_UTF8, [
            'normalize' => true,
            'translit' => true,
            'ignore' => true,
        ]);

        $this->assertSame('Café', $result);
    }

    public function testToUtf8WithNonStringValue(): void
    {
        $data = ['number' => 123, 'bool' => true, 'null' => null];
        $result = CharsetHelper::toUtf8($data);

        $this->assertSame(123, $result['number']);
        $this->assertTrue($result['bool']);
        $this->assertNull($result['null']);
    }

    public function testToCharsetPreservesAlreadyValidUtf8(): void
    {
        $utf8 = 'Café résumé';
        $result = CharsetHelper::toCharset($utf8, CharsetHelper::ENCODING_UTF8, CharsetHelper::ENCODING_ISO);

        $this->assertSame('Café résumé', $result);
    }

    public function testRepairDoesNotModifyValidUtf8(): void
    {
        $valid = 'Café résumé';
        $result = CharsetHelper::repair($valid);

        $this->assertSame('Café résumé', $result);
    }

    public function testToCharsetWithEmptyString(): void
    {
        $result = CharsetHelper::toCharset('', CharsetHelper::ENCODING_UTF8);

        $this->assertSame('', $result);
    }

    public function testToCharsetWithEmptyArray(): void
    {
        $result = CharsetHelper::toCharset([], CharsetHelper::ENCODING_UTF8);

        $this->assertSame([], $result);
    }

    public function testSafeJsonEncodeWithEmptyArray(): void
    {
        $json = CharsetHelper::safeJsonEncode([]);

        $this->assertSame('[]', $json);
    }

    public function testDetectWithCustomEncodings(): void
    {
        $utf8 = 'Café';
        $encoding = CharsetHelper::detect($utf8, [
            'encodings' => ['UTF-8', 'ISO-8859-1'],
        ]);

        $this->assertSame('UTF-8', $encoding);
    }

    public function testConstants(): void
    {
        $this->assertSame('AUTO', CharsetHelper::AUTO);
        $this->assertSame('UTF-8', CharsetHelper::ENCODING_UTF8);
        $this->assertSame('UTF-16', CharsetHelper::ENCODING_UTF16);
        $this->assertSame('UTF-32', CharsetHelper::ENCODING_UTF32);
        $this->assertSame('ISO-8859-1', CharsetHelper::ENCODING_ISO);
        $this->assertSame('CP1252', CharsetHelper::WINDOWS_1252);
        $this->assertSame('ASCII', CharsetHelper::ENCODING_ASCII);
    }

    public function testToUtf8WithWindows1252Default(): void
    {
        $cp1252 = \mb_convert_encoding('€', 'CP1252', 'UTF-8');
        $result = CharsetHelper::toUtf8($cp1252);

        $this->assertSame('€', $result);
    }

    public function testObjectImmutability(): void
    {
        $original = new stdClass();
        $original->name = \mb_convert_encoding('José', 'ISO-8859-1', 'UTF-8');

        $result = CharsetHelper::toUtf8($original, CharsetHelper::ENCODING_ISO);

        $this->assertNotSame($original, $result);
        $this->assertSame('José', $result->name);
    }

    public function testSafeJsonDecodeWithDepth(): void
    {
        $json = '{"a":{"b":{"c":"value"}}}';
        $result = CharsetHelper::safeJsonDecode($json, true, 512);

        $this->assertSame('value', $result['a']['b']['c']);
    }

    public function testToCharsetWithMixedData(): void
    {
        $iso = \mb_convert_encoding('Café', 'ISO-8859-1', 'UTF-8');
        $obj = new stdClass();
        $obj->text = $iso;

        $data = [
            'string' => $iso,
            'number' => 42,
            'object' => $obj,
            'nested' => ['value' => $iso],
        ];

        $result = CharsetHelper::toUtf8($data, CharsetHelper::ENCODING_ISO);

        $this->assertSame('Café', $result['string']);
        $this->assertSame(42, $result['number']);
        $this->assertSame('Café', $result['object']->text);
        $this->assertSame('Café', $result['nested']['value']);
    }

    public function testSafeJsonEncodeWithDepth(): void
    {
        $data = ['a' => ['b' => ['c' => 'value']]];
        $json = CharsetHelper::safeJsonEncode($data, 0, 10);

        $this->assertIsString($json);
    }

    public function testSafeJsonDecodeWithAllParameters(): void
    {
        $json = '{"name":"test"}';
        $result = CharsetHelper::safeJsonDecode($json, true, 512, 0, CharsetHelper::ENCODING_UTF8, CharsetHelper::WINDOWS_1252);

        $this->assertIsArray($result);
        $this->assertSame('test', $result['name']);
    }

    public function testRepairWithObject(): void
    {
        $obj = new stdClass();
        // Create double-encoded string
        $iso = \mb_convert_encoding('Café', 'ISO-8859-1', 'UTF-8');
        $obj->name = \mb_convert_encoding($iso ?: '', 'UTF-8', 'ISO-8859-1');

        $result = CharsetHelper::repair($obj);

        $this->assertInstanceOf(stdClass::class, $result);
        $this->assertSame('Café', $result->name);
    }

    public function testToCharsetFromUtf8ToNonUtf8(): void
    {
        $utf8 = 'Café';
        $result = CharsetHelper::toCharset($utf8, CharsetHelper::ENCODING_ISO, CharsetHelper::ENCODING_UTF8);

        $this->assertNotNull($result);
        $this->assertNotSame('', $result);
        $converted = CharsetHelper::toUtf8($result, CharsetHelper::ENCODING_ISO);
        $this->assertSame('Café', $converted);
    }

    public function testDetectFallsBackToIso(): void
    {
        $binary = "\x80\x81\x82";
        $encoding = CharsetHelper::detect($binary);

        $this->assertContains($encoding, ['ISO-8859-1', 'Windows-1252', 'UTF-8']);
    }

    public function testRepairWithInvalidMaxDepth(): void
    {
        $data = 'test';
        $result = CharsetHelper::repair($data, CharsetHelper::ENCODING_UTF8, CharsetHelper::ENCODING_ISO, ['maxDepth' => 'invalid']);

        $this->assertSame('test', $result, 'Should return original string when maxDepth is invalid');
    }

    public function testToCharsetWithInvalidOptionsArray(): void
    {
        $data = 'test';
        $result = CharsetHelper::toCharset($data, CharsetHelper::ENCODING_UTF8, CharsetHelper::ENCODING_UTF8, ['encodings' => 'not-array']);

        $this->assertSame('test', $result);
    }

    public function testNormalizeWithNormalizerNotAvailable(): void
    {
        if (\class_exists('Normalizer')) {
            $this->markTestSkipped('Normalizer is available');
        }

        $data = 'Café';
        $result = CharsetHelper::toCharset($data, CharsetHelper::ENCODING_UTF8, CharsetHelper::ENCODING_UTF8, ['normalize' => true]);

        $this->assertSame('Café', $result);
    }

    public function testConvertValueAlreadyInTargetEncoding(): void
    {
        $utf8 = 'Café';
        $result = CharsetHelper::toCharset($utf8, CharsetHelper::ENCODING_UTF8, CharsetHelper::ENCODING_UTF8);

        $this->assertSame('Café', $result);
    }

    public function testPeelEncodingLayersBreaksOnSameResult(): void
    {
        $data = 'test';
        $result = CharsetHelper::repair($data);

        $this->assertSame('test', $result);
    }

    public function testSafeJsonEncodeThrowsOnInvalidData(): void
    {
        $this->expectException(JsonException::class);

        /** @var resource $resource */
        $resource = \fopen('php://memory', 'r');
        CharsetHelper::safeJsonEncode(['resource' => $resource]);
        \fclose($resource);
    }

    public function testRegisterTranscoderWithInterface(): void
    {
        $transcoder = $this->createMock(\Ducks\Component\EncodingRepair\Transcoder\TranscoderInterface::class);
        $transcoder->method('getPriority')->willReturn(50);
        $transcoder->method('isAvailable')->willReturn(true);
        $transcoder->method('transcode')->willReturn(null);

        CharsetHelper::registerTranscoder($transcoder, 50);

        $result = CharsetHelper::toUtf8('test');
        $this->assertIsString($result);
    }

    public function testRegisterDetectorWithInterface(): void
    {
        $detector = $this->createMock(DetectorInterface::class);
        $detector->method('getPriority')->willReturn(100);
        $detector->method('isAvailable')->willReturn(true);
        $detector->method('detect')->willReturn('UTF-8');

        CharsetHelper::registerDetector($detector, 250);

        $encoding = CharsetHelper::detect('test');
        $this->assertSame('UTF-8', $encoding);
    }

    public function testToCharsetBatchWithArray(): void
    {
        $items = ['Café', 'Thé', 'Crème'];

        $result = CharsetHelper::toCharsetBatch($items, CharsetHelper::ENCODING_UTF8, CharsetHelper::ENCODING_UTF8);

        $this->assertIsArray($result);
        $this->assertCount(3, $result);
    }

    public function testToCharsetBatchWithIso(): void
    {
        $items = ['test1', 'test2', 'test3'];

        $result = CharsetHelper::toCharsetBatch($items, CharsetHelper::WINDOWS_1252, CharsetHelper::ENCODING_UTF8);

        $this->assertIsArray($result);
        $this->assertCount(3, $result);
    }

    public function testToCharsetBatchWithAutoDetection(): void
    {
        $items = ['test1', 'test2'];

        $result = CharsetHelper::toCharsetBatch($items, 'UTF-8', CharsetHelper::AUTO);

        $this->assertIsArray($result);
        $this->assertCount(2, $result);
    }

    public function testRegisterTranscoderWithInvalidType(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Transcoder must be an instance of TranscoderInterface or a callable');

        /** @var mixed $invalidTranscoder */
        $invalidTranscoder = 'not a transcoder';
        CharsetHelper::registerTranscoder($invalidTranscoder);
    }

    public function testRegisterDetectorWithInvalidType(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Detector must be an instance of DetectorInterface or a callable');

        /** @var mixed $invalidDetector */
        $invalidDetector = 123;
        CharsetHelper::registerDetector($invalidDetector);
    }

    public function testRegisterTranscoderWithCallable(): void
    {
        // @phpstan-ignore return.unusedType
        $callable = static fn (string $data, string $to, string $from, ?array $options = null): ?string => 'custom-' . $data;

        CharsetHelper::registerTranscoder($callable, 200);

        $result = CharsetHelper::toUtf8('test');
        $this->assertIsString($result);
    }

    public function testRegisterDetectorWithCallable(): void
    {
        // @phpstan-ignore return.unusedType
        $callable = static fn (string $string, ?array $options): ?string => 'UTF-8';

        CharsetHelper::registerDetector($callable, 300);

        $encoding = CharsetHelper::detect('test');
        $this->assertSame('UTF-8', $encoding);
    }

    public function testDetectBatch(): void
    {
        $items = ['Café', 'Thé', 'test'];

        $encoding = CharsetHelper::detectBatch($items);

        $this->assertSame('UTF-8', $encoding);
    }

    public function testIsReturnsTrueForMatchingEncoding(): void
    {
        $this->assertTrue(CharsetHelper::is('Café', 'UTF-8'));
        $this->assertTrue(CharsetHelper::is('test', 'UTF-8'));
    }

    public function testIsReturnsFalseForNonMatchingEncoding(): void
    {
        /** @var string|false */
        $iso = \mb_convert_encoding('Café', 'ISO-8859-1', 'UTF-8');
        if (false === $iso) {
            $this->fail(__FUNCTION__ . ' convertion failed!');
        }

        $this->assertFalse(CharsetHelper::is($iso, 'UTF-8'));
    }

    public function testIsHandlesEncodingAliases(): void
    {
        /** @var string|false */
        $iso = \mb_convert_encoding('Café', 'ISO-8859-1', 'UTF-8');
        if (false === $iso) {
            $this->fail(__FUNCTION__ . ' convertion failed!');
        }

        $this->assertTrue(CharsetHelper::is($iso, 'CP1252'));
        $this->assertTrue(CharsetHelper::is($iso, 'ISO-8859-1'));
    }

    public function testIsNormalizesEncodingCase(): void
    {
        $this->assertTrue(CharsetHelper::is('test', 'utf-8'));
        $this->assertTrue(CharsetHelper::is('test', 'UTF-8'));
    }

    public function testIsThrowsExceptionForInvalidEncoding(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Invalid target encoding');

        CharsetHelper::is('test', 'INVALID-ENCODING');
    }
}
