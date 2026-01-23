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
use Ducks\Component\EncodingRepair\Interpreter\PropertyMapperInterface;
use RuntimeException;
use PHPUnit\Framework\TestCase;

final class CharsetProcessorEdgeCasesTest extends TestCase
{
    public function testRegisterPropertyMapperWithoutObjectInterpreter(): void
    {
        $processor = new CharsetProcessor();

        // Create a new InterpreterChain without ObjectInterpreter
        $reflectionClass = new \ReflectionClass($processor);
        $property = $reflectionClass->getProperty('interpreterChain');
        $property->setAccessible(true);

        $emptyChain = new \Ducks\Component\EncodingRepair\Interpreter\InterpreterChain();
        $property->setValue($processor, $emptyChain);

        $mapper = $this->createMock(PropertyMapperInterface::class);

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('ObjectInterpreter not registered in chain');

        $processor->registerPropertyMapper(\stdClass::class, $mapper);
    }

    public function testRepairWithNonStringValue(): void
    {
        $processor = new CharsetProcessor();

        $result = $processor->repair(123, 'UTF-8', 'ISO-8859-1');

        $this->assertSame(123, $result);
    }

    public function testRepairWithArrayContainingNonStrings(): void
    {
        $processor = new CharsetProcessor();

        $data = [
            'number' => 123,
            'null' => null,
            'bool' => true,
        ];

        $result = $processor->repair($data, 'UTF-8', 'ISO-8859-1');

        $this->assertIsArray($result);
        $this->assertSame(123, $result['number']);
        $this->assertNull($result['null']);
        $this->assertTrue($result['bool']);
    }

    public function testToCharsetWithAutoDetection(): void
    {
        $processor = new CharsetProcessor();

        $result = $processor->toCharset('Café', 'UTF-8', CharsetProcessor::AUTO);

        $this->assertSame('Café', $result);
    }

    public function testAddEncodingsWithDuplicates(): void
    {
        $processor = new CharsetProcessor();

        $processor->addEncodings('SHIFT_JIS');
        $processor->addEncodings('SHIFT_JIS'); // Duplicate

        $encodings = $processor->getEncodings();
        $count = \count(\array_filter($encodings, static fn($e) => 'SHIFT_JIS' === $e));

        $this->assertSame(1, $count);
    }

    public function testValidateEncodingWithLowercase(): void
    {
        $processor = new CharsetProcessor();

        $processor->addEncodings('custom-encoding');

        $result = $processor->toCharset('test', 'UTF-8', 'custom-encoding');

        $this->assertIsString($result);
    }

    public function testPeelEncodingLayersWithInvalidUtf8(): void
    {
        $processor = new CharsetProcessor();

        // Create a string that's not valid UTF-8
        $invalidUtf8 = "\xFF\xFE";

        $result = $processor->repair($invalidUtf8, 'UTF-8', 'ISO-8859-1');

        $this->assertIsString($result);
    }

    public function testConvertStringWithFailedTranscode(): void
    {
        $processor = new CharsetProcessor();
        $processor->resetTranscoders(); // Remove all transcoders

        $result = $processor->toCharset('test', 'UTF-8', 'ISO-8859-1');

        // Should return original data when transcoding fails
        $this->assertSame('test', $result);
    }

    public function testNormalizeWithNormalizerClass(): void
    {
        if (!\class_exists(\Normalizer::class)) {
            $this->markTestSkipped('Normalizer class not available');
        }

        $processor = new CharsetProcessor();

        // String with combining characters
        $combining = "e\u{0301}"; // é as e + combining acute accent

        $result = $processor->toCharset($combining, 'UTF-8', 'UTF-8', ['normalize' => true]);

        $this->assertIsString($result);
    }

    public function testNormalizeWithoutNormalizerClass(): void
    {
        $processor = new CharsetProcessor();

        // Test with normalize option when Normalizer might not be available
        $result = $processor->toCharset('Café', 'UTF-8', 'UTF-8', ['normalize' => false]);

        $this->assertSame('Café', $result);
    }

    public function testConvertValueWithStringAlreadyInTargetEncoding(): void
    {
        $processor = new CharsetProcessor();

        $utf8String = 'Café';
        $result = $processor->toCharset($utf8String, 'UTF-8', 'UTF-8');

        $this->assertSame($utf8String, $result);
    }

    public function testPeelEncodingLayersBreaksOnNullTranscode(): void
    {
        $processor = new CharsetProcessor();
        $processor->resetTranscoders(); // Remove all transcoders to force null return

        $result = $processor->repair('Café', 'UTF-8', 'ISO-8859-1', ['maxDepth' => 5]);

        $this->assertIsString($result);
    }

    public function testRepairWithMaxDepthZero(): void
    {
        $processor = new CharsetProcessor();

        $result = $processor->repair('Café', 'UTF-8', 'ISO-8859-1', ['maxDepth' => 0]);

        $this->assertSame('Café', $result);
    }

    public function testToCharsetBatchWithEmptyArray(): void
    {
        $processor = new CharsetProcessor();

        $result = $processor->toCharsetBatch([], 'UTF-8', 'ISO-8859-1');

        $this->assertIsArray($result);
        $this->assertEmpty($result);
    }

    public function testDetectBatchWithAllEmptyStrings(): void
    {
        $processor = new CharsetProcessor();

        $encoding = $processor->detectBatch(['', '', '']);

        $this->assertSame(CharsetProcessor::ENCODING_ISO, $encoding);
    }
}
