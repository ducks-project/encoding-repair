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
use Ducks\Component\EncodingRepair\Cleaner\TaggedStrategy;
use PHPUnit\Framework\TestCase;

final class TaggedStrategyTest extends TestCase
{
    public function testExecuteAppliesOnlyMatchingTags(): void
    {
        $cleaner1 = $this->createMock(CleanerInterface::class);
        $cleaner1->method('isAvailable')->willReturn(true);
        $cleaner1->method('clean')->willReturn('cleaned1');

        $cleaner2 = $this->createMock(CleanerInterface::class);
        $cleaner2->method('isAvailable')->willReturn(true);
        $cleaner2->expects($this->never())->method('clean');

        $strategy = new TaggedStrategy(['tag1']);
        $strategy->registerTags($cleaner1, ['tag1']);
        $strategy->registerTags($cleaner2, ['tag2']);

        $result = $strategy->execute([$cleaner1, $cleaner2], 'input', 'UTF-8', []);

        $this->assertSame('cleaned1', $result);
    }

    public function testExecuteAppliesMultipleMatchingCleaners(): void
    {
        $cleaner1 = $this->createMock(CleanerInterface::class);
        $cleaner1->method('isAvailable')->willReturn(true);
        $cleaner1->method('clean')->willReturn('step1');

        $cleaner2 = $this->createMock(CleanerInterface::class);
        $cleaner2->method('isAvailable')->willReturn(true);
        $cleaner2->method('clean')->willReturn('step2');

        $strategy = new TaggedStrategy(['tag1', 'tag2']);
        $strategy->registerTags($cleaner1, ['tag1']);
        $strategy->registerTags($cleaner2, ['tag2']);

        $result = $strategy->execute([$cleaner1, $cleaner2], 'input', 'UTF-8', []);

        $this->assertSame('step2', $result);
    }

    public function testExecuteSkipsCleanersWithoutMatchingTags(): void
    {
        $cleaner = $this->createMock(CleanerInterface::class);
        $cleaner->method('isAvailable')->willReturn(true);
        $cleaner->expects($this->never())->method('clean');

        $strategy = new TaggedStrategy(['tag1']);
        $strategy->registerTags($cleaner, ['tag2', 'tag3']);

        $result = $strategy->execute([$cleaner], 'input', 'UTF-8', []);

        $this->assertNull($result);
    }

    public function testExecuteHandlesCleanersWithoutRegisteredTags(): void
    {
        $cleaner = $this->createMock(CleanerInterface::class);
        $cleaner->method('isAvailable')->willReturn(true);
        $cleaner->expects($this->never())->method('clean');

        $strategy = new TaggedStrategy(['tag1']);

        $result = $strategy->execute([$cleaner], 'input', 'UTF-8', []);

        $this->assertNull($result);
    }

    public function testExecuteSkipsUnavailableCleaners(): void
    {
        $cleaner1 = $this->createMock(CleanerInterface::class);
        $cleaner1->method('isAvailable')->willReturn(false);

        $cleaner2 = $this->createMock(CleanerInterface::class);
        $cleaner2->method('isAvailable')->willReturn(true);
        $cleaner2->method('clean')->willReturn('cleaned');

        $strategy = new TaggedStrategy(['tag1']);
        $strategy->registerTags($cleaner1, ['tag1']);
        $strategy->registerTags($cleaner2, ['tag1']);

        $result = $strategy->execute([$cleaner1, $cleaner2], 'input', 'UTF-8', []);

        $this->assertSame('cleaned', $result);
    }

    public function testExecuteReturnsNullWhenNoCleanerSucceeds(): void
    {
        $cleaner = $this->createMock(CleanerInterface::class);
        $cleaner->method('isAvailable')->willReturn(true);
        $cleaner->method('clean')->willReturn(null);

        $strategy = new TaggedStrategy(['tag1']);
        $strategy->registerTags($cleaner, ['tag1']);

        $result = $strategy->execute([$cleaner], 'input', 'UTF-8', []);

        $this->assertNull($result);
    }

    public function testExecuteHandlesEmptyCleanerList(): void
    {
        $strategy = new TaggedStrategy(['tag1']);
        $result = $strategy->execute([], 'input', 'UTF-8', []);

        $this->assertNull($result);
    }

    public function testExecuteChainsMatchingCleanersSuccessively(): void
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

        $strategy = new TaggedStrategy(['tag1']);
        $strategy->registerTags($cleaner1, ['tag1']);
        $strategy->registerTags($cleaner2, ['tag1']);

        $result = $strategy->execute([$cleaner1, $cleaner2], 'input', 'UTF-8', []);

        $this->assertSame('final', $result);
    }
}
