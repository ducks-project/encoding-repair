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
use Ducks\Component\EncodingRepair\Interpreter\InterpreterChain;
use Ducks\Component\EncodingRepair\Interpreter\ObjectInterpreter;
use Ducks\Component\EncodingRepair\Interpreter\ArrayInterpreter;
use Ducks\Component\EncodingRepair\Interpreter\StringInterpreter;
use PHPUnit\Framework\TestCase;

/**
 * Tests for bug fixes applied to the codebase.
 *
 * @final
 */
final class BugFixesTest extends TestCase
{
    /**
     * Test that getObjectInterpreter() does not consume the queue.
     */
    public function testGetObjectInterpreterDoesNotConsumeQueue(): void
    {
        $chain = new InterpreterChain();
        $objectInterpreter = new ObjectInterpreter($chain);
        $chain->register($objectInterpreter, 30);

        $obj1 = $chain->getObjectInterpreter();
        $obj2 = $chain->getObjectInterpreter();

        $this->assertSame($obj1, $obj2);
        $this->assertInstanceOf(ObjectInterpreter::class, $obj1);
    }

    /**
     * Test that safeJsonDecode() accepts valid null JSON.
     */
    public function testSafeJsonDecodeWithValidNull(): void
    {
        $processor = new CharsetProcessor();
        $result = $processor->safeJsonDecode('null');

        $this->assertNull($result);
    }

    /**
     * Test that safeJsonDecode() accepts valid JSON with null values.
     */
    public function testSafeJsonDecodeWithNullInArray(): void
    {
        $processor = new CharsetProcessor();
        $result = $processor->safeJsonDecode('{"key": null}', true);

        $this->assertIsArray($result);
        $this->assertArrayHasKey('key', $result);
        $this->assertNull($result['key']);
    }

    /**
     * Test that safeJsonDecode() throws JsonException on invalid JSON.
     */
    public function testSafeJsonDecodeThrowsJsonException(): void
    {
        $this->expectException(\JsonException::class);

        $processor = new CharsetProcessor();
        $processor->safeJsonDecode('invalid json{');
    }

    /**
     * Test that safeJsonEncode() throws JsonException on encoding error.
     */
    public function testSafeJsonEncodeThrowsJsonException(): void
    {
        $this->expectException(\JsonException::class);

        $processor = new CharsetProcessor();
        // Create a resource which cannot be JSON encoded
        $resource = \fopen('php://memory', 'r');

        if (false !== $resource) {
            $processor->safeJsonEncode(['resource' => $resource]);
            \fclose($resource);
        }
    }

    /**
     * Test that detectBatch() samples multiple elements.
     */
    public function testDetectBatchWithMixedContent(): void
    {
        $processor = new CharsetProcessor();

        $items = [
            '',
            'ASCII',
            'Café', // UTF-8
            'More UTF-8 content with accents: éàù'
        ];

        $encoding = $processor->detectBatch($items, ['maxSamples' => 5]);
        $this->assertSame('UTF-8', $encoding);
    }

    /**
     * Test that detectBatch() uses longest sample.
     */
    public function testDetectBatchUsesLongestSample(): void
    {
        $processor = new CharsetProcessor();

        $items = [
            'a',
            'ab',
            'This is a much longer UTF-8 string with accents: éàùç',
            'abc'
        ];

        $encoding = $processor->detectBatch($items, ['maxSamples' => 5]);
        $this->assertSame('UTF-8', $encoding);
    }

    /**
     * Test that detectBatch() returns default for empty items.
     */
    public function testDetectBatchWithEmptyItems(): void
    {
        $processor = new CharsetProcessor();

        $items = ['', '', ''];

        $encoding = $processor->detectBatch($items);
        $this->assertSame('ISO-8859-1', $encoding);
    }

    /**
     * Test that interpret() still works after getObjectInterpreter().
     */
    public function testInterpretWorksAfterGetObjectInterpreter(): void
    {
        $chain = new InterpreterChain();
        $chain->register(new StringInterpreter(), 100);
        $chain->register(new ArrayInterpreter($chain), 50);
        $chain->register(new ObjectInterpreter($chain), 30);

        // Call getObjectInterpreter first
        $objInterpreter = $chain->getObjectInterpreter();
        $this->assertNotNull($objInterpreter);

        // Interpret should still work
        $callback = static fn($value) => \is_string($value) ? \strtoupper($value) : $value;
        $result = $chain->interpret('test', $callback, []);

        $this->assertSame('TEST', $result);
    }

    /**
     * Test that detectBatch() with maxSamples=1 uses first sample only.
     */
    public function testDetectBatchWithMaxSamplesOne(): void
    {
        $processor = new CharsetProcessor();

        $items = [
            'First UTF-8: café',
            'Second',
            'Third'
        ];

        $encoding = $processor->detectBatch($items, ['maxSamples' => 1]);
        $this->assertSame('UTF-8', $encoding);
    }

    /**
     * Test that detectBatch() handles invalid maxSamples values.
     */
    public function testDetectBatchWithInvalidMaxSamples(): void
    {
        $processor = new CharsetProcessor();

        $items = ['UTF-8: café'];

        // Negative value should fallback to default (1)
        $encoding = $processor->detectBatch($items, ['maxSamples' => -5]);
        $this->assertSame('UTF-8', $encoding);

        // Zero should fallback to default (1)
        $encoding = $processor->detectBatch($items, ['maxSamples' => 0]);
        $this->assertSame('UTF-8', $encoding);

        // Non-integer should fallback to default (1)
        $encoding = $processor->detectBatch($items, ['maxSamples' => 'invalid']);
        $this->assertSame('UTF-8', $encoding);
    }

    /**
     * Test that detectBatch() default behavior uses first sample.
     */
    public function testDetectBatchDefaultBehavior(): void
    {
        $processor = new CharsetProcessor();

        $items = [
            'First: café',
            'Second',
            'Third'
        ];

        // Without maxSamples option, should use default (1)
        $encoding = $processor->detectBatch($items);
        $this->assertSame('UTF-8', $encoding);
    }
}
