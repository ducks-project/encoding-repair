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
}
