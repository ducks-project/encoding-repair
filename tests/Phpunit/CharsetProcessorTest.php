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

use Ducks\Component\EncodingRepair\CharsetProcessor;
use Ducks\Component\EncodingRepair\CharsetProcessorInterface;
use Ducks\Component\EncodingRepair\Transcoder\IconvTranscoder;
use Ducks\Component\EncodingRepair\Transcoder\MbStringTranscoder;
use Ducks\Component\EncodingRepair\Detector\MbStringDetector;
use Ducks\Component\EncodingRepair\Interpreter\InterpreterChain;
use Ducks\Component\EncodingRepair\Interpreter\PropertyMapperInterface;
use Ducks\Component\EncodingRepair\Interpreter\TypeInterpreterInterface;
use Ducks\Component\EncodingRepair\Tests\Common\Word;
use PHPUnit\Framework\TestCase;

final class CharsetProcessorTest extends TestCase
{
    public function testUnregisterTranscoder(): void
    {
        $processor = new CharsetProcessor();
        $transcoder = new MbStringTranscoder();

        $processor->registerTranscoder($transcoder, 200);
        $result1 = $processor->toUtf8('test');
        $this->assertSame('test', $result1);

        $processor->unregisterTranscoder($transcoder);
        $result2 = $processor->toUtf8('test');
        $this->assertSame('test', $result2);
    }

    public function testUnregisterDetector(): void
    {
        $processor = new CharsetProcessor();
        $detector = new MbStringDetector();

        $processor->registerDetector($detector, 200);
        $encoding1 = $processor->detect('Café');
        $this->assertSame('UTF-8', $encoding1);

        $processor->unregisterDetector($detector);
        $encoding2 = $processor->detect('Café');
        $this->assertSame('UTF-8', $encoding2);
    }

    public function testUnregisterTranscoderWithMultipleInstances(): void
    {
        $processor = new CharsetProcessor();
        $transcoder1 = new IconvTranscoder();
        $transcoder2 = new MbStringTranscoder();

        $processor->registerTranscoder($transcoder1, 100);
        $processor->registerTranscoder($transcoder2, 50);

        $processor->unregisterTranscoder($transcoder1);

        $result = $processor->toUtf8('test');
        $this->assertSame('test', $result);
    }

    public function testUnregisterNonExistentTranscoder(): void
    {
        $processor = new CharsetProcessor();
        $transcoder = new MbStringTranscoder();

        $processor->unregisterTranscoder($transcoder);

        $result = $processor->toUtf8('test');
        $this->assertSame('test', $result);
    }

    public function testRemoveEncodings(): void
    {
        $processor = new CharsetProcessor();

        $processor->addEncodings('SHIFT_JIS', 'EUC-JP');
        $encodings = $processor->getEncodings();
        $this->assertContains('SHIFT_JIS', $encodings);
        $this->assertContains('EUC-JP', $encodings);

        $processor->removeEncodings('SHIFT_JIS');
        $encodings = $processor->getEncodings();
        $this->assertNotContains('SHIFT_JIS', $encodings);
        $this->assertContains('EUC-JP', $encodings);
    }

    public function testGetEncodings(): void
    {
        $processor = new CharsetProcessor();

        $encodings = $processor->getEncodings();
        $this->assertIsArray($encodings);
        $this->assertContains('UTF-8', $encodings);
        $this->assertContains('AUTO', $encodings);
    }

    public function testRepairWithInvalidMaxDepth(): void
    {
        $processor = new CharsetProcessor();

        $result = $processor->repair(
            Word::getBadUtf8Word(),
            'UTF-8',
            'ISO-8859-1',
            ['maxDepth' => 'invalid']
        );

        $this->assertSame(Word::getGoodUtf8Word(), $result);
    }

    public function testRemoveMultipleEncodings(): void
    {
        $processor = new CharsetProcessor();

        $processor->addEncodings('SHIFT_JIS', 'EUC-JP', 'GB2312');
        $processor->removeEncodings('SHIFT_JIS', 'EUC-JP');

        $encodings = $processor->getEncodings();
        $this->assertNotContains('SHIFT_JIS', $encodings);
        $this->assertNotContains('EUC-JP', $encodings);
        $this->assertContains('GB2312', $encodings);
    }

    public function testQueueTranscoders(): void
    {
        $processor = new CharsetProcessor();
        $transcoder1 = new IconvTranscoder();
        $transcoder2 = new MbStringTranscoder();

        $processor->resetTranscoders();
        $processor->queueTranscoders($transcoder1, $transcoder2);

        $result = $processor->toUtf8('test');
        $this->assertSame('test', $result);
    }

    public function testQueueDetectors(): void
    {
        $processor = new CharsetProcessor();
        $detector1 = new MbStringDetector();
        $detector2 = new MbStringDetector();

        $processor->resetDetectors();
        $processor->queueDetectors($detector1, $detector2);

        $encoding = $processor->detect(Word::getGoodUtf8Word());

        $this->assertSame(CharsetProcessorInterface::ENCODING_UTF8, $encoding);
    }

    public function testResetEncodings(): void
    {
        $processor = new CharsetProcessor();

        $processor->addEncodings('SHIFT_JIS', 'EUC-JP');
        $encodings = $processor->getEncodings();
        $this->assertContains('SHIFT_JIS', $encodings);

        $processor->resetEncodings();
        $encodings = $processor->getEncodings();
        $this->assertNotContains('SHIFT_JIS', $encodings);
        $this->assertContains('UTF-8', $encodings);
        $this->assertContains('AUTO', $encodings);
    }

    public function testNormalizationWithCombiningCharacters(): void
    {
        if (!\class_exists(\Normalizer::class)) {
            $this->markTestSkipped('Normalizer class not available');
        }

        $processor = new CharsetProcessor();
        $nfd = "e\u{0301}";

        $result = $processor->toCharset(
            $nfd,
            CharsetProcessorInterface::ENCODING_UTF8,
            CharsetProcessorInterface::ENCODING_UTF8,
            ['normalize' => true]
        );
        $expected = "\u{00E9}";

        $this->assertSame($expected, $result);
        $this->assertSame(2, strlen($result));
    }

    public function testNormalizationDisabled(): void
    {
        if (!\class_exists(\Normalizer::class)) {
            $this->markTestSkipped('Normalizer class not available');
        }

        $processor = new CharsetProcessor();
        $nfd = "e\u{0301}";

        $result = $processor->toCharset(
            $nfd,
            CharsetProcessorInterface::ENCODING_UTF8,
            CharsetProcessorInterface::ENCODING_UTF8,
            ['normalize' => false]
        );

        $this->assertSame($nfd, $result);
        $this->assertSame(3, strlen($result));
    }

    public function testNormalizationOnlyForUtf8(): void
    {
        if (!\class_exists(\Normalizer::class)) {
            $this->markTestSkipped('Normalizer class not available');
        }

        $processor = new CharsetProcessor();
        $nfd = "e\u{0301}";

        $result = $processor->toCharset(
            $nfd,
            CharsetProcessorInterface::ENCODING_ISO,
            CharsetProcessorInterface::ENCODING_UTF8,
            ['normalize' => true]
        );

        $this->assertIsString($result);
        $this->assertSame(2, strlen($result));
    }

    public function testNormalizationWithRealWorldExample(): void
    {
        if (!\class_exists(\Normalizer::class)) {
            $this->markTestSkipped('Normalizer class not available');
        }

        $processor = new CharsetProcessor();
        $nfd = "Cafe\u{0301}";

        $result = $processor->toUtf8(
            $nfd,
            CharsetProcessorInterface::ENCODING_UTF8,
            ['normalize' => true]
        );
        $expected = "Caf\u{00E9}";

        $this->assertSame($expected, $result);
    }

    public function testNormalizationDefaultBehavior(): void
    {
        if (!\class_exists(\Normalizer::class)) {
            $this->markTestSkipped('Normalizer class not available');
        }

        $processor = new CharsetProcessor();
        $nfd = "e\u{0301}";

        $result = $processor->toUtf8($nfd, CharsetProcessorInterface::ENCODING_UTF8);
        $expected = "\u{00E9}";

        $this->assertSame($expected, $result);
    }

    public function testNormalizationWithFalsyValues(): void
    {
        if (!\class_exists(\Normalizer::class)) {
            $this->markTestSkipped('Normalizer class not available');
        }

        $processor = new CharsetProcessor();
        $nfd = "e\u{0301}";
        $expected = "\u{00E9}";

        $result = $processor->toUtf8(
            $nfd,
            CharsetProcessorInterface::ENCODING_UTF8,
            ['normalize' => false]
        );
        $this->assertSame($nfd, $result);

        $falsyValues = [0, '0', '', null];

        foreach ($falsyValues as $falsyValue) {
            $result = $processor->toUtf8(
                $nfd,
                CharsetProcessorInterface::ENCODING_UTF8,
                ['normalize' => $falsyValue]
            );
            $this->assertSame($expected, $result);
        }
    }

    public function testAddEncodings(): void
    {
        $processor = new CharsetProcessor();

        $processor->addEncodings('SHIFT_JIS', 'EUC-JP');
        $encodings = $processor->getEncodings();

        $this->assertContains('SHIFT_JIS', $encodings);
        $this->assertContains('EUC-JP', $encodings);
    }

    public function testDetect(): void
    {
        $processor = new CharsetProcessor();

        $encoding = $processor->detect('Café');

        $this->assertSame(CharsetProcessorInterface::ENCODING_UTF8, $encoding);
    }

    public function testDetectBatch(): void
    {
        $processor = new CharsetProcessor();

        $items = Word::GOOD;
        $encoding = $processor->detectBatch($items);

        $this->assertSame(CharsetProcessorInterface::ENCODING_UTF8, $encoding);
    }

    public function testDetectBatchWithInvalidMaxSamples(): void
    {
        $processor = new CharsetProcessor();

        $items = Word::GOOD;
        $encoding = $processor->detectBatch($items, ['maxSamples' => -1]);

        $this->assertSame(CharsetProcessorInterface::ENCODING_UTF8, $encoding);

        $encoding = $processor->detectBatch($items, ['maxSamples' => 0]);
        $this->assertSame(CharsetProcessorInterface::ENCODING_UTF8, $encoding);

        $encoding = $processor->detectBatch($items, ['maxSamples' => 'invalid']);
        $this->assertSame(CharsetProcessorInterface::ENCODING_UTF8, $encoding);
    }

    public function testDetectBatchReturnsUtf8WhenNoSamples(): void
    {
        $processor = new CharsetProcessor();

        $encoding = $processor->detectBatch([]);
        $this->assertSame(CharsetProcessorInterface::ENCODING_UTF8, $encoding);

        $encoding = $processor->detectBatch(['', '', '']);
        $this->assertSame(CharsetProcessorInterface::ENCODING_UTF8, $encoding);

        $encoding = $processor->detectBatch([123, null, []]);
        $this->assertSame(CharsetProcessorInterface::ENCODING_UTF8, $encoding);
    }

    public function testDetectBatchWithMultipleSamplesReturnsLongest(): void
    {
        $processor = new CharsetProcessor();

        $items = [
            'short',
            Word::getBadUtf8Word(),
            'This is a much longer string that should be selected for detection',
        ];

        $encoding = $processor->detectBatch($items, ['maxSamples' => 3]);
        $this->assertSame(CharsetProcessorInterface::ENCODING_UTF8, $encoding);
    }

    public function testRegisterTranscoder(): void
    {
        $processor = new CharsetProcessor();
        $transcoder = new MbStringTranscoder();

        $processor->registerTranscoder($transcoder, 150);
        $result = $processor->toUtf8('test');

        $this->assertSame('test', $result);
    }

    public function testRegisterDetector(): void
    {
        $processor = new CharsetProcessor();
        $detector = new MbStringDetector();

        $processor->registerDetector($detector, 150);
        $encoding = $processor->detect('test');

        $this->assertSame('UTF-8', $encoding);
    }

    public function testRegisterInterpreter(): void
    {
        $processor = new CharsetProcessor();
        $interpreter = $this->createMock(TypeInterpreterInterface::class);
        $interpreter->method('getPriority')->willReturn(50);
        $interpreter->method('supports')->willReturn(false);

        $processor->registerInterpreter($interpreter, 50);
        $result = $processor->toUtf8('test');

        $this->assertSame('test', $result);
    }

    public function testRegisterPropertyMapper(): void
    {
        $processor = new CharsetProcessor();
        $mapper = $this->createMock(PropertyMapperInterface::class);

        $processor->registerPropertyMapper(\stdClass::class, $mapper);
        $result = $processor->toUtf8('test');

        $this->assertSame('test', $result);
    }

    public function testRepair(): void
    {
        $processor = new CharsetProcessor();

        $result = $processor->repair(Word::getBadUtf8Word());

        $this->assertSame(Word::getGoodUtf8Word(), $result);
    }

    public function testResetTranscoders(): void
    {
        $processor = new CharsetProcessor();

        $processor->resetTranscoders();
        $processor->registerTranscoder(new MbStringTranscoder(), 100);
        $result = $processor->toUtf8('test');

        $this->assertSame('test', $result);
    }

    public function testResetDetectors(): void
    {
        $processor = new CharsetProcessor();

        $processor->resetDetectors();
        $processor->registerDetector(new MbStringDetector(), 100);
        $encoding = $processor->detect('test');

        $this->assertSame('UTF-8', $encoding);
    }

    public function testResetInterpreters(): void
    {
        $processor = new CharsetProcessor();

        $processor->resetInterpreters();
        $result = $processor->toUtf8('test');

        $this->assertSame('test', $result);
    }

    public function testUnregisterInterpreter(): void
    {
        $processor = new CharsetProcessor();
        $interpreter = $this->createMock(TypeInterpreterInterface::class);
        $interpreter->method('getPriority')->willReturn(50);

        $processor->registerInterpreter($interpreter, 50);
        $processor->unregisterInterpreter($interpreter);
        $result = $processor->toUtf8('test');

        $this->assertSame('test', $result);
    }

    public function testToCharset(): void
    {
        $processor = new CharsetProcessor();

        $result = $processor->toCharset('Café', 'UTF-8', 'UTF-8');

        $this->assertSame('Café', $result);
    }

    public function testToCharsetBatch(): void
    {
        $processor = new CharsetProcessor();

        $items = ['test1', 'test2', 'test3'];
        $result = $processor->toCharsetBatch($items, 'UTF-8', 'UTF-8');

        $this->assertIsArray($result);
        $this->assertCount(3, $result);
    }

    public function testToIso(): void
    {
        $processor = new CharsetProcessor();

        $result = $processor->toIso('test', 'UTF-8');

        $this->assertIsString($result);
    }

    public function testToIsoBatch(): void
    {
        $processor = new CharsetProcessor();

        $items = ['test1', 'test2'];
        $result = $processor->toIsoBatch($items, 'UTF-8');

        $this->assertIsArray($result);
        $this->assertCount(2, $result);
    }

    public function testToUtf8(): void
    {
        $processor = new CharsetProcessor();

        $result = $processor->toUtf8('test', 'UTF-8');

        $this->assertSame('test', $result);
    }

    public function testToUtf8Batch(): void
    {
        $processor = new CharsetProcessor();

        $items = ['test1', 'test2'];
        $result = $processor->toUtf8Batch($items, 'UTF-8');

        $this->assertIsArray($result);
        $this->assertCount(2, $result);
    }

    public function testSafeJsonEncode(): void
    {
        $processor = new CharsetProcessor();

        $data = ['name' => 'Café'];
        $json = $processor->safeJsonEncode($data);

        $this->assertIsString($json);
        $this->assertStringContainsString('Caf', $json);
    }

    public function testSafeJsonDecode(): void
    {
        $processor = new CharsetProcessor();

        $json = '{"name":"Café"}';
        $result = $processor->safeJsonDecode($json, true);

        $this->assertIsArray($result);
        $this->assertSame('Café', $result['name']);
    }

    public function testRegisterPropertyMapperThrowsWhenObjectInterpreterNotRegistered(): void
    {
        $processor = new CharsetProcessor();

        $reflectionClass = new \ReflectionClass($processor);
        $property = $reflectionClass->getProperty('interpreterChain');
        $property->setAccessible(true);

        $emptyChain = new InterpreterChain();
        $property->setValue($processor, $emptyChain);

        $mapper = $this->createMock(PropertyMapperInterface::class);

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('ObjectInterpreter not registered in chain');

        $processor->registerPropertyMapper(\stdClass::class, $mapper);
    }
}
