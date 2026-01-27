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
use ReflectionClass;

final class CharsetProcessorRepairTest extends TestCase
{
    /**
     * Wrapper to invoke private method.
     *
     * @param object $object
     * @param string $methodName
     * @param list<mixed> $args
     *
     * @return string
     */
    private function invokePrivateMethod(
        object $object,
        string $methodName,
        array $args = []
    ): string {
        $reflection = new ReflectionClass($object);
        $method = $reflection->getMethod($methodName);
        $method->setAccessible(true);

        return $method->invokeArgs($object, $args);
    }

    public function testRepairByPatternReplacementWithC382Pattern(): void
    {
        $processor = new CharsetProcessor();
        $corrupted = "Caf\xC3\x82\xC3\xA9";

        $result = $this->invokePrivateMethod($processor, 'repairByPatternReplacement', [$corrupted]);

        $this->assertSame("Caf\xC3\xA9", $result);
    }

    public function testRepairByPatternReplacementWithC383C2Pattern(): void
    {
        $processor = new CharsetProcessor();
        $corrupted = "\xC3\x83\xC2\xA9";

        $result = $this->invokePrivateMethod($processor, 'repairByPatternReplacement', [$corrupted]);

        $this->assertSame("\xC3\xA9", $result);
    }

    public function testRepairByPatternReplacementWithBothPatterns(): void
    {
        $processor = new CharsetProcessor();
        $corrupted = "Test\xC3\x82\xC3\x83\xC2\xA0String";

        $result = $this->invokePrivateMethod($processor, 'repairByPatternReplacement', [$corrupted]);

        $this->assertSame("Test\xC3\xA0String", $result);
    }

    public function testRepairByPatternReplacementWithNoCorruption(): void
    {
        $processor = new CharsetProcessor();
        $clean = "Café";

        $result = $this->invokePrivateMethod($processor, 'repairByPatternReplacement', [$clean]);

        $this->assertSame($clean, $result);
    }

    public function testRepairByPatternReplacementWithEmptyString(): void
    {
        $processor = new CharsetProcessor();
        $empty = '';

        $result = $this->invokePrivateMethod($processor, 'repairByPatternReplacement', [$empty]);

        $this->assertSame('', $result);
    }

    public function testRepairByPatternReplacementWithMultipleOccurrences(): void
    {
        $processor = new CharsetProcessor();
        $corrupted = "\xC3\x82\xC3\x82\xC3\x82";

        $result = $this->invokePrivateMethod($processor, 'repairByPatternReplacement', [$corrupted]);

        $this->assertSame('', $result);
    }

    public function testRepairByTranscodeWithSingleLayer(): void
    {
        $simple = 'BrÃ©sil';

        $processor = new CharsetProcessor();

        $result = $this->invokePrivateMethod($processor, 'repairByTranscode', [$simple, 'ISO-8859-1', 5]);

        $this->assertSame('Café', $result);
    }

    public function testRepairByTranscodeWithMaxDepthReached(): void
    {
        $processor = new CharsetProcessor();
        $value = 'Café';

        $result = $this->invokePrivateMethod($processor, 'repairByTranscode', [$value, 'ISO-8859-1', 1]);

        $this->assertIsString($result);
    }

    public function testRepairByTranscodeWithInvalidUtf8(): void
    {
        $processor = new CharsetProcessor();
        $invalid = "\xFF\xFE";

        $result = $this->invokePrivateMethod($processor, 'repairByTranscode', [$invalid, 'ISO-8859-1', 5]);

        $this->assertSame($invalid, $result);
    }

    public function testRepairByTranscodeWithNoChange(): void
    {
        $processor = new CharsetProcessor();
        $clean = 'Simple ASCII text';

        $result = $this->invokePrivateMethod($processor, 'repairByTranscode', [$clean, 'ISO-8859-1', 5]);

        $this->assertSame($clean, $result);
    }

    public function testRepairByTranscodeWithZeroDepth(): void
    {
        $processor = new CharsetProcessor();
        $value = 'Café';

        $result = $this->invokePrivateMethod($processor, 'repairByTranscode', [$value, 'ISO-8859-1', 0]);

        $this->assertSame($value, $result);
    }

    public function testRepairByTranscodeStopsWhenResultIsLonger(): void
    {
        $processor = new CharsetProcessor();
        $value = 'test';

        $result = $this->invokePrivateMethod($processor, 'repairByTranscode', [$value, 'ISO-8859-1', 10]);

        $this->assertIsString($result);
    }

    public function testRepairByTranscodeWithMultipleLayers(): void
    {
        $triple = 'BrÃÂÃÂ©sil';
        $processor = new CharsetProcessor();

        $result = $this->invokePrivateMethod($processor, 'repairByTranscode', [$triple, 'ISO-8859-1', 5]);

        $this->assertIsString($result);
    }

    public function testRepairByPatternReplacementWithRealWorldExample(): void
    {
        $simple = 'BrÃ©sil';

        $processor = new CharsetProcessor();

        $result = $this->invokePrivateMethod($processor, 'repairByPatternReplacement', [$simple]);

        $this->assertIsString($result);
    }
}
