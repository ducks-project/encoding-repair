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

use Ducks\Component\EncodingRepair\Detector\PregMatchDetector;
use PHPUnit\Framework\TestCase;

/**
 * @covers \Ducks\Component\EncodingRepair\Detector\PregMatchDetector
 */
final class PregMatchDetectorTest extends TestCase
{
    private PregMatchDetector $detector;

    protected function setUp(): void
    {
        $this->detector = new PregMatchDetector();
    }

    public function testDetectEmptyString(): void
    {
        $result = $this->detector->detect('', []);

        $this->assertSame('ASCII', $result);
    }

    public function testDetectAsciiString(): void
    {
        $result = $this->detector->detect('Hello World', []);

        $this->assertSame('ASCII', $result);
    }

    public function testDetectAsciiWithNumbers(): void
    {
        $result = $this->detector->detect('Test123', []);

        $this->assertSame('ASCII', $result);
    }

    public function testDetectUtf8String(): void
    {
        $result = $this->detector->detect('Café', []);

        $this->assertSame('UTF-8', $result);
    }

    public function testDetectUtf8WithEmoji(): void
    {
        $result = $this->detector->detect('Hello 👋', []);

        $this->assertSame('UTF-8', $result);
    }

    public function testDetectUtf8WithChinese(): void
    {
        $result = $this->detector->detect('你好', []);

        $this->assertSame('UTF-8', $result);
    }

    public function testDetectInvalidUtf8ReturnsNull(): void
    {
        $result = $this->detector->detect("\xFF\xFE", []);

        $this->assertNull($result);
    }

    public function testDetectIso88591ReturnsNull(): void
    {
        /** @var string|false $iso */
        $iso = \mb_convert_encoding('Café', 'ISO-8859-1', 'UTF-8');
        if (false === $iso) {
            $this->fail(__FUNCTION__ . ' convertion failed!');
        }

        $result = $this->detector->detect($iso, []);

        $this->assertNull($result);
    }

    public function testGetPriority(): void
    {
        $priority = $this->detector->getPriority();

        $this->assertSame(150, $priority);
    }

    public function testIsAvailable(): void
    {
        $available = $this->detector->isAvailable();

        $this->assertTrue($available);
    }
}
