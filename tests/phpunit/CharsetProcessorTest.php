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
use Ducks\Component\EncodingRepair\Transcoder\IconvTranscoder;
use Ducks\Component\EncodingRepair\Transcoder\MbStringTranscoder;
use Ducks\Component\EncodingRepair\Detector\MbStringDetector;
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

        /** @var string|false $corrupted */
        $corrupted = \mb_convert_encoding('Café', 'ISO-8859-1', 'UTF-8');
        if (false === $corrupted) {
            $this->fail('mb_convert_encoding failed');
        }

        /** @var string|false $doubleEncoded */
        $doubleEncoded = \mb_convert_encoding($corrupted, 'UTF-8', 'ISO-8859-1');
        if (false === $doubleEncoded) {
            $this->fail('mb_convert_encoding failed');
        }

        $result = $processor->repair($doubleEncoded, 'UTF-8', 'ISO-8859-1', ['maxDepth' => 'invalid']);
        $this->assertSame('Café', $result);
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

        $encoding = $processor->detect('Café');
        $this->assertSame('UTF-8', $encoding);
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
}
