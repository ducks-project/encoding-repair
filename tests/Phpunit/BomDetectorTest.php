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

use Ducks\Component\EncodingRepair\Detector\BomDetector;
use PHPUnit\Framework\TestCase;

/**
 * @covers \Ducks\Component\EncodingRepair\Detector\BomDetector
 */
final class BomDetectorTest extends TestCase
{
    private BomDetector $detector;

    protected function setUp(): void
    {
        $this->detector = new BomDetector();
    }

    public function testDetectUtf8Bom(): void
    {
        $string = "\xEF\xBB\xBF" . 'Hello World';

        $result = $this->detector->detect($string, []);

        $this->assertSame('UTF-8', $result);
    }

    public function testDetectUtf16LeBom(): void
    {
        $string = "\xFF\xFE" . 'Hello';

        $result = $this->detector->detect($string, []);

        $this->assertSame('UTF-16LE', $result);
    }

    public function testDetectUtf16BeBom(): void
    {
        $string = "\xFE\xFF" . 'Hello';

        $result = $this->detector->detect($string, []);

        $this->assertSame('UTF-16BE', $result);
    }

    public function testDetectUtf32LeBom(): void
    {
        $string = "\xFF\xFE\x00\x00" . 'Hello';

        $result = $this->detector->detect($string, []);

        $this->assertSame('UTF-32LE', $result);
    }

    public function testDetectUtf32BeBom(): void
    {
        $string = "\x00\x00\xFE\xFF" . 'Hello';

        $result = $this->detector->detect($string, []);

        $this->assertSame('UTF-32BE', $result);
    }

    public function testDetectNoBomReturnsNull(): void
    {
        $string = 'Hello World';

        $result = $this->detector->detect($string, []);

        $this->assertNull($result);
    }

    public function testDetectEmptyStringReturnsNull(): void
    {
        $result = $this->detector->detect('', []);

        $this->assertNull($result);
    }

    public function testDetectSingleByteReturnsNull(): void
    {
        $result = $this->detector->detect('A', []);

        $this->assertNull($result);
    }

    public function testDetectUtf32LeBeforeUtf16Le(): void
    {
        // UTF-32 LE starts with FF FE 00 00
        // UTF-16 LE starts with FF FE
        // Must detect UTF-32 LE, not UTF-16 LE
        $string = "\xFF\xFE\x00\x00";

        $result = $this->detector->detect($string, []);

        $this->assertSame('UTF-32LE', $result);
    }

    public function testGetPriority(): void
    {
        $priority = $this->detector->getPriority();

        $this->assertSame(160, $priority);
    }

    public function testIsAvailable(): void
    {
        $available = $this->detector->isAvailable();

        $this->assertTrue($available);
    }
}
