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

use Ducks\Component\EncodingRepair\Detector\MbStringDetector;
use PHPUnit\Framework\TestCase;

final class MbStringDetectorTest extends TestCase
{
    public function testGetPriority(): void
    {
        $detector = new MbStringDetector();
        $this->assertSame(100, $detector->getPriority());
    }

    public function testIsAvailable(): void
    {
        $detector = new MbStringDetector();
        $this->assertTrue($detector->isAvailable());
    }

    public function testDetectUtf8(): void
    {
        $detector = new MbStringDetector();
        $result = $detector->detect('Café', []);

        $this->assertSame('UTF-8', $result);
    }

    public function testDetectWithCustomEncodings(): void
    {
        $detector = new MbStringDetector();
        $result = $detector->detect('test', ['encodings' => ['UTF-8', 'ISO-8859-1']]);

        $this->assertIsString($result);
    }

    public function testDetectReturnsNullOnFailure(): void
    {
        $detector = new MbStringDetector();
        $result = $detector->detect("\x80\x81", ['encodings' => ['ASCII']]);

        $this->assertNull($result);
    }

    public function testDetectWithInvalidEncodingsOption(): void
    {
        $detector = new MbStringDetector();
        $result = $detector->detect('test', ['encodings' => 'not-array']);

        $this->assertIsString($result);
    }
}
