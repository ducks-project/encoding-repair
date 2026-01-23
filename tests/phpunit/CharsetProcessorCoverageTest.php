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

namespace Ducks\Component\EncodingRepair\Tests\phpunit;

use Ducks\Component\EncodingRepair\CharsetProcessor;
use PHPUnit\Framework\TestCase;

final class CharsetProcessorCoverageTest extends TestCase
{
    public function testToUtf8WithString(): void
    {
        $processor = new CharsetProcessor();

        $result = $processor->toUtf8('test', CharsetProcessor::ENCODING_UTF8);

        $this->assertSame('test', $result);
    }

    public function testToIsoWithString(): void
    {
        $processor = new CharsetProcessor();

        $result = $processor->toIso('test', CharsetProcessor::ENCODING_UTF8);

        $this->assertIsString($result);
    }

    public function testToUtf8BatchWithArray(): void
    {
        $processor = new CharsetProcessor();
        $items = ['test1', 'test2', 'test3'];

        $result = $processor->toUtf8Batch($items, CharsetProcessor::ENCODING_UTF8);

        $this->assertIsArray($result);
        $this->assertCount(3, $result);
    }

    public function testToIsoBatchWithArray(): void
    {
        $processor = new CharsetProcessor();
        $items = ['test1', 'test2', 'test3'];

        $result = $processor->toIsoBatch($items, CharsetProcessor::ENCODING_UTF8);

        $this->assertIsArray($result);
        $this->assertCount(3, $result);
    }

    public function testDetectBatchWithEmptyStrings(): void
    {
        $processor = new CharsetProcessor();
        $items = ['', '', 'Café'];

        $encoding = $processor->detectBatch($items);

        $this->assertSame('UTF-8', $encoding);
    }

    public function testDetectBatchWithNoStrings(): void
    {
        $processor = new CharsetProcessor();
        $items = [123, null, []];

        $encoding = $processor->detectBatch($items);

        $this->assertSame(CharsetProcessor::ENCODING_ISO, $encoding);
    }

    public function testConvertValueWithNonString(): void
    {
        $processor = new CharsetProcessor();

        $result = $processor->toCharset(123, 'UTF-8', 'ISO-8859-1');

        $this->assertSame(123, $result);
    }

    public function testConvertValueWithUtf8ToNonUtf8(): void
    {
        $processor = new CharsetProcessor();

        $result = $processor->toCharset('Café', 'ISO-8859-1', 'UTF-8');

        $this->assertIsString($result);
    }

    public function testPeelEncodingLayersWithNoLayers(): void
    {
        $processor = new CharsetProcessor();

        $result = $processor->repair('test', 'UTF-8', 'ISO-8859-1');

        $this->assertSame('test', $result);
    }

    public function testApplyToObjectWithNestedProperties(): void
    {
        $processor = new CharsetProcessor();

        $obj = new \stdClass();
        $obj->name = 'test';
        $obj->nested = new \stdClass();
        $obj->nested->value = 'nested';

        $result = $processor->toCharset($obj, 'UTF-8', 'UTF-8');

        $this->assertInstanceOf(\stdClass::class, $result);
        $this->assertSame('test', $result->name);
        $this->assertSame('nested', $result->nested->value);
    }

    public function testValidateEncodingWithUppercase(): void
    {
        $processor = new CharsetProcessor();

        $processor->addEncodings('CUSTOM-ENCODING');

        $result = $processor->toCharset('test', 'UTF-8', 'CUSTOM-ENCODING');

        $this->assertIsString($result);
    }

    public function testSafeJsonDecodeWithNullResult(): void
    {
        $processor = new CharsetProcessor();

        $result = $processor->safeJsonDecode('null');

        $this->assertNull($result);
    }
}
