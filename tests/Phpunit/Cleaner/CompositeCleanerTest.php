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
use Ducks\Component\EncodingRepair\Cleaner\CompositeCleaner;
use Ducks\Component\EncodingRepair\Cleaner\FirstMatchStrategy;
use Ducks\Component\EncodingRepair\Cleaner\PipelineStrategy;
use PHPUnit\Framework\TestCase;

final class CompositeCleanerTest extends TestCase
{
    public function testDefaultStrategyIsPipeline(): void
    {
        $cleaner1 = $this->createMock(CleanerInterface::class);
        $cleaner1->method('isAvailable')->willReturn(true);
        $cleaner1->method('clean')->willReturn('step1');

        $cleaner2 = $this->createMock(CleanerInterface::class);
        $cleaner2->method('isAvailable')->willReturn(true);
        $cleaner2->method('clean')->willReturn('step2');

        $composite = new CompositeCleaner(null, 100, $cleaner1, $cleaner2);
        $result = $composite->clean('input', 'UTF-8', []);

        $this->assertSame('step2', $result);
    }

    public function testCustomStrategy(): void
    {
        $cleaner1 = $this->createMock(CleanerInterface::class);
        $cleaner1->method('isAvailable')->willReturn(true);
        $cleaner1->method('clean')->willReturn('first');

        $cleaner2 = $this->createMock(CleanerInterface::class);
        $cleaner2->method('isAvailable')->willReturn(true);
        $cleaner2->expects($this->never())->method('clean');

        $composite = new CompositeCleaner(new FirstMatchStrategy(), 100, $cleaner1, $cleaner2);
        $result = $composite->clean('input', 'UTF-8', []);

        $this->assertSame('first', $result);
    }

    public function testGetPriority(): void
    {
        $composite = new CompositeCleaner(null, 150);

        $this->assertSame(150, $composite->getPriority());
    }

    public function testIsAvailableReturnsTrueWhenAtLeastOneCleanerAvailable(): void
    {
        $cleaner1 = $this->createMock(CleanerInterface::class);
        $cleaner1->method('isAvailable')->willReturn(false);

        $cleaner2 = $this->createMock(CleanerInterface::class);
        $cleaner2->method('isAvailable')->willReturn(true);

        $composite = new CompositeCleaner(null, 100, $cleaner1, $cleaner2);

        $this->assertTrue($composite->isAvailable());
    }

    public function testIsAvailableReturnsFalseWhenAllCleanersUnavailable(): void
    {
        $cleaner1 = $this->createMock(CleanerInterface::class);
        $cleaner1->method('isAvailable')->willReturn(false);

        $cleaner2 = $this->createMock(CleanerInterface::class);
        $cleaner2->method('isAvailable')->willReturn(false);

        $composite = new CompositeCleaner(null, 100, $cleaner1, $cleaner2);

        $this->assertFalse($composite->isAvailable());
    }

    public function testIsAvailableReturnsFalseWhenNoCleaners(): void
    {
        $composite = new CompositeCleaner();

        $this->assertFalse($composite->isAvailable());
    }
}
