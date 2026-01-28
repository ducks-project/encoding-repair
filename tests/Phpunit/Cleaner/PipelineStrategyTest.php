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

namespace Ducks\Component\EncodingRepair\Tests\Phpunit\Cleaner;

use Ducks\Component\EncodingRepair\Cleaner\CleanerInterface;
use Ducks\Component\EncodingRepair\Cleaner\PipelineStrategy;
use PHPUnit\Framework\TestCase;

final class PipelineStrategyTest extends TestCase
{
    public function testExecuteAppliesAllCleaners(): void
    {
        $cleaner1 = $this->createMock(CleanerInterface::class);
        $cleaner1->method('isAvailable')->willReturn(true);
        $cleaner1->method('clean')->willReturn('step1');

        $cleaner2 = $this->createMock(CleanerInterface::class);
        $cleaner2->method('isAvailable')->willReturn(true);
        $cleaner2->method('clean')->willReturn('step2');

        $strategy = new PipelineStrategy();
        $result = $strategy->execute([$cleaner1, $cleaner2], 'input', 'UTF-8', []);

        $this->assertSame('step2', $result);
    }

    public function testExecuteSkipsUnavailableCleaners(): void
    {
        $cleaner1 = $this->createMock(CleanerInterface::class);
        $cleaner1->method('isAvailable')->willReturn(false);

        $cleaner2 = $this->createMock(CleanerInterface::class);
        $cleaner2->method('isAvailable')->willReturn(true);
        $cleaner2->method('clean')->willReturn('cleaned');

        $strategy = new PipelineStrategy();
        $result = $strategy->execute([$cleaner1, $cleaner2], 'input', 'UTF-8', []);

        $this->assertSame('cleaned', $result);
    }

    public function testExecuteReturnsNullWhenNoCleanerSucceeds(): void
    {
        $cleaner = $this->createMock(CleanerInterface::class);
        $cleaner->method('isAvailable')->willReturn(true);
        $cleaner->method('clean')->willReturn(null);

        $strategy = new PipelineStrategy();
        $result = $strategy->execute([$cleaner], 'input', 'UTF-8', []);

        $this->assertNull($result);
    }

    public function testExecuteHandlesEmptyCleanerList(): void
    {
        $strategy = new PipelineStrategy();
        $result = $strategy->execute([], 'input', 'UTF-8', []);

        $this->assertNull($result);
    }

    public function testExecuteChainsCleanersSuccessively(): void
    {
        $cleaner1 = $this->createMock(CleanerInterface::class);
        $cleaner1->method('isAvailable')->willReturn(true);
        $cleaner1->expects($this->once())
            ->method('clean')
            ->with('input', 'UTF-8', [])
            ->willReturn('intermediate');

        $cleaner2 = $this->createMock(CleanerInterface::class);
        $cleaner2->method('isAvailable')->willReturn(true);
        $cleaner2->expects($this->once())
            ->method('clean')
            ->with('intermediate', 'UTF-8', [])
            ->willReturn('final');

        $strategy = new PipelineStrategy();
        $result = $strategy->execute([$cleaner1, $cleaner2], 'input', 'UTF-8', []);

        $this->assertSame('final', $result);
    }

    public function testExecuteSkipsCleanerReturningNull(): void
    {
        $cleaner1 = $this->createMock(CleanerInterface::class);
        $cleaner1->method('isAvailable')->willReturn(true);
        $cleaner1->method('clean')->willReturn(null);

        $cleaner2 = $this->createMock(CleanerInterface::class);
        $cleaner2->method('isAvailable')->willReturn(true);
        $cleaner2->expects($this->once())
            ->method('clean')
            ->with('input', 'UTF-8', [])
            ->willReturn('cleaned');

        $strategy = new PipelineStrategy();
        $result = $strategy->execute([$cleaner1, $cleaner2], 'input', 'UTF-8', []);

        $this->assertSame('cleaned', $result);
    }
}
