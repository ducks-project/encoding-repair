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

use Ducks\Component\EncodingRepair\Transcoder\UConverterTranscoder;
use PHPUnit\Framework\TestCase;

final class UConverterTranscoderTest extends TestCase
{
    public function testGetPriority(): void
    {
        $transcoder = new UConverterTranscoder();

        $this->assertSame(100, $transcoder->getPriority());
    }

    public function testIsAvailable(): void
    {
        $transcoder = new UConverterTranscoder();

        $this->assertIsBool($transcoder->isAvailable());
    }

    public function testTranscodeReturnsNullWhenNotAvailable(): void
    {
        if (!\class_exists(\UConverter::class)) {
            $transcoder = new UConverterTranscoder();

            $result = $transcoder->transcode('test', 'UTF-8', 'ISO-8859-1', []);

            $this->assertNull($result);
        } else {
            $this->markTestSkipped('UConverter is available, cannot test unavailable scenario');
        }
    }

    public function testTranscodeWithUConverter(): void
    {
        if (!\class_exists(\UConverter::class)) {
            $this->markTestSkipped('UConverter extension not available');
        }

        $transcoder = new UConverterTranscoder();
        /** @var string|false $iso */
        $iso = \mb_convert_encoding('Café', 'ISO-8859-1', 'UTF-8');

        if (false === $iso) {
            $this->fail('mb_convert_encoding failed');
        }

        $result = $transcoder->transcode($iso, 'UTF-8', 'ISO-8859-1', []);

        $this->assertSame('Café', $result);
    }

    public function testTranscodeWithOptions(): void
    {
        if (!\class_exists(\UConverter::class)) {
            $this->markTestSkipped('UConverter extension not available');
        }

        $transcoder = new UConverterTranscoder();
        $data = 'test';

        $result = $transcoder->transcode($data, 'UTF-8', 'UTF-8', ['to_subst' => '?']);

        $this->assertIsString($result);
    }

    public function testTranscodeIgnoresIrrelevantOptions(): void
    {
        if (!\class_exists(\UConverter::class)) {
            $this->markTestSkipped('UConverter extension not available');
        }

        $transcoder = new UConverterTranscoder();
        $data = 'test';

        $result = $transcoder->transcode($data, 'UTF-8', 'UTF-8', [
            'to_subst' => '?',
            'irrelevant' => 'ignored',
        ]);

        $this->assertIsString($result);
    }
}
