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

use Ducks\Component\EncodingRepair\Cleaner\CleanerChain;
use Ducks\Component\EncodingRepair\Cleaner\CleanerInterface;
use Ducks\Component\EncodingRepair\Cleaner\IconvCleaner;
use Ducks\Component\EncodingRepair\Cleaner\MbScrubCleaner;
use Ducks\Component\EncodingRepair\Cleaner\PregMatchCleaner;
use PHPUnit\Framework\TestCase;

final class CleanerChainTest extends TestCase
{
    public function testRegisterCleaner(): void
    {
        $chain = new CleanerChain();
        $cleaner = new MbScrubCleaner();

        $chain->register($cleaner);

        $result = $chain->clean('Test', 'UTF-8', []);
        $this->assertIsString($result);
    }

    public function testRegisterWithCustomPriority(): void
    {
        $chain = new CleanerChain();
        $cleaner = new MbScrubCleaner();

        $chain->register($cleaner, 200);

        $result = $chain->clean('Test', 'UTF-8', []);
        $this->assertIsString($result);
    }

    public function testUnregisterCleaner(): void
    {
        $chain = new CleanerChain();
        $cleaner = new MbScrubCleaner();

        $chain->register($cleaner);
        $chain->unregister($cleaner);

        $result = $chain->clean('Test', 'UTF-8', []);
        $this->assertNull($result);
    }

    public function testCleanExecutesInPriorityOrder(): void
    {
        $chain = new CleanerChain();
        $chain->register(new MbScrubCleaner());
        $chain->register(new PregMatchCleaner());
        $chain->register(new IconvCleaner());

        $result = $chain->clean('Café', 'UTF-8', []);
        $this->assertSame('Café', $result);
    }

    public function testCleanStopsAtFirstSuccess(): void
    {
        $chain = new CleanerChain();

        $mock1 = $this->createMock(CleanerInterface::class);
        $mock1->method('getPriority')->willReturn(100);
        $mock1->method('isAvailable')->willReturn(true);
        $mock1->method('clean')->willReturn('cleaned');

        $mock2 = $this->createMock(CleanerInterface::class);
        $mock2->method('getPriority')->willReturn(50);
        $mock2->method('isAvailable')->willReturn(true);
        $mock2->expects($this->never())->method('clean');

        $chain->register($mock1);
        $chain->register($mock2);

        $result = $chain->clean('test', 'UTF-8', []);
        $this->assertSame('cleaned', $result);
    }

    public function testCleanReturnsNullWhenAllFail(): void
    {
        $chain = new CleanerChain();

        $mock = $this->createMock(CleanerInterface::class);
        $mock->method('getPriority')->willReturn(100);
        $mock->method('isAvailable')->willReturn(true);
        $mock->method('clean')->willReturn(null);

        $chain->register($mock);

        $result = $chain->clean('test', 'UTF-8', []);
        $this->assertNull($result);
    }

    public function testCleanSkipsUnavailableCleaners(): void
    {
        $chain = new CleanerChain();

        $unavailable = $this->createMock(CleanerInterface::class);
        $unavailable->method('getPriority')->willReturn(100);
        $unavailable->method('isAvailable')->willReturn(false);
        $unavailable->expects($this->never())->method('clean');

        $available = $this->createMock(CleanerInterface::class);
        $available->method('getPriority')->willReturn(50);
        $available->method('isAvailable')->willReturn(true);
        $available->method('clean')->willReturn('cleaned');

        $chain->register($unavailable);
        $chain->register($available);

        $result = $chain->clean('test', 'UTF-8', []);
        $this->assertSame('cleaned', $result);
    }
}
