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

use Ducks\Component\EncodingRepair\Detector\FileInfoDetector;
use PHPUnit\Framework\TestCase;

final class FileInfoDetectorTest extends TestCase
{
    public function testGetPriority(): void
    {
        $detector = new FileInfoDetector();
        $this->assertSame(50, $detector->getPriority());
    }

    public function testIsAvailable(): void
    {
        $detector = new FileInfoDetector();
        $this->assertTrue($detector->isAvailable());
    }

    public function testDetectUtf8(): void
    {
        $detector = new FileInfoDetector();
        $result = $detector->detect('Café', []);
        
        $this->assertContains($result, ['UTF-8', 'US-ASCII', null]);
    }

    public function testDetectWithOptions(): void
    {
        $detector = new FileInfoDetector();
        $result = $detector->detect('test', ['finfo_flags' => FILEINFO_NONE]);
        
        $this->assertIsString($result);
    }

    public function testDetectReturnNullForBinary(): void
    {
        $detector = new FileInfoDetector();
        $binary = "\x00\x01\x02\x03";
        $result = $detector->detect($binary, []);
        
        $this->assertNull($result);
    }
}
