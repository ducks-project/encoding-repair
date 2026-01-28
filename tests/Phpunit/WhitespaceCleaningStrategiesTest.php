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

use PHPUnit\Framework\TestCase;

/**
 * Test reliability of whitespace cleaning strategies.
 */
final class WhitespaceCleaningStrategiesTest extends TestCase
{
    /**
     * @return array<string, array{string, string}>
     */
    public function provideWhitespaceData(): array
    {
        return [
            'double spaces' => ['Hello  world', 'Hello world'],
            'tabs' => ["Hello\t\tworld", 'Hello world'],
            'mixed whitespace' => ["Hello  \t  world", 'Hello world'],
            'newlines' => ["Hello\n\nworld", 'Hello world'],
            'nbsp' => ["Hello\xC2\xA0\xC2\xA0world", 'Hello world'],
            'mixed all' => ["Hello  \t\n\xC2\xA0  world", 'Hello world'],
            'leading/trailing' => ["  Hello  world  ", ' Hello world '],
            'multiple types' => ["a  b\t\tc\n\nd\xC2\xA0\xC2\xA0e", 'a b c d e'],
        ];
    }

    /**
     * @dataProvider provideWhitespaceData
     */
    public function testPregReplace(string $input, string $expected): void
    {
        $result = \preg_replace('/[\s\xC2\xA0]+/u', ' ', $input);
        $this->assertSame($expected, $result);
    }

    /**
     * @dataProvider provideWhitespaceData
     */
    public function testPregReplaceSimple(string $input, string $expected): void
    {
        $result = \preg_replace('/\s+/', ' ', $input);
        $this->assertIsString($result);
    }

    /**
     * @dataProvider provideWhitespaceData
     */
    public function testStrReplaceChain(string $input, string $expected): void
    {
        $result = \str_replace(['  ', "\t\t", "\n\n", "\xC2\xA0"], ' ', $input);
        while (\str_contains($result, '  ')) {
            $result = \str_replace('  ', ' ', $result);
        }
        $this->assertIsString($result);
    }

    /**
     * @dataProvider provideWhitespaceData
     */
    public function testStrtr(string $input, string $expected): void
    {
        $result = \strtr($input, ["\t" => ' ', "\n" => ' ', "\r" => ' ', "\xC2\xA0" => ' ']);
        while (\str_contains($result, '  ')) {
            $result = \str_replace('  ', ' ', $result);
        }
        $this->assertIsString($result);
    }

    /**
     * @dataProvider provideWhitespaceData
     */
    public function testMbEregReplace(string $input, string $expected): void
    {
        $result = \mb_ereg_replace('\s+', ' ', $input);
        $this->assertIsString($result);
    }

    /**
     * @dataProvider provideWhitespaceData
     */
    public function testSplitJoin(string $input, string $expected): void
    {
        $split = \preg_split('/\s+/', $input, -1, \PREG_SPLIT_NO_EMPTY);
        if (false === $split) {
            $this->fail('split failed');
        }

        $result = \implode(' ', $split);
        $this->assertIsString($result);
    }

    /**
     * @dataProvider provideWhitespaceData
     */
    public function testHybrid(string $input, string $expected): void
    {
        $result = \str_replace(["\t", "\n", "\r", "\xC2\xA0"], ' ', $input);
        $result = \preg_replace('/\s{2,}/', ' ', $result);
        $this->assertSame($expected, $result);
    }

    public function testEdgeCases(): void
    {
        $this->assertSame('', \preg_replace('/[\s\xC2\xA0]+/u', ' ', ''));
        $this->assertSame(' ', \preg_replace('/[\s\xC2\xA0]+/u', ' ', ' '));
        $this->assertSame('HelloWorld', \preg_replace('/[\s\xC2\xA0]+/u', ' ', 'HelloWorld'));
        $this->assertSame(' ', \preg_replace('/[\s\xC2\xA0]+/u', ' ', "  \t\n  "));
    }
}
