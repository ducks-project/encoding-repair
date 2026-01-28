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
use Ducks\Component\EncodingRepair\Cleaner\FirstMatchStrategy;
use PHPUnit\Framework\TestCase;

final class FirstMatchStrategyTest extends TestCase
{
    public function testExecuteStopsAtFirstSuccess(): void
    {
        $cleaner1 = $this->createMock(CleanerInterface::class);
        $cleaner1->method('isAvailable')->willReturn(true);
        $cleaner1->method('clean')->willReturn('first');

        $cleaner2 = $this->createMock(CleanerInterface::class);
        $cleaner2->method('isAvailable')->willReturn(true);
        $cleaner2->expects($this->never())->method('clean');

        $strategy = new FirstMatchStrategy();
        $result = $strategy->execute([$cleaner1, $cleaner2], 'input', 'UTF-8', []);

        $this->assertSame('first', $result);
    }

    public function testExecuteSkipsUnavailableCleaners(): void
    {
        $cleaner1 = $this->createMock(CleanerInterface::class);
        $cleaner1->method('isAvailable')->willReturn(false);

        $cleaner2 = $this->createMock(CleanerInterface::class);
        $cleaner2->method('isAvailable')->willReturn(true);
        $cleaner2->method('clean')->willReturn('second');

        $strategy = new FirstMatchStrategy();
        $result = $strategy->execute([$cleaner1, $cleaner2], 'input', 'UTF-8', []);

        $this->assertSame('second', $result);
    }

    public function testExecuteReturnsNullWhenNoCleanerSucceeds(): void
    {
        $cleaner = $this->createMock(CleanerInterface::class);
        $cleaner->method('isAvailable')->willReturn(true);
        $cleaner->method('clean')->willReturn(null);

        $strategy = new FirstMatchStrategy();
        $result = $strategy->execute([$cleaner], 'input', 'UTF-8', []);

        $this->assertNull($result);
    }

    public function testExecuteHandlesEmptyCleanerList(): void
    {
        $strategy = new FirstMatchStrategy();
        $result = $strategy->execute([], 'input', 'UTF-8', []);

        $this->assertNull($result);
    }

    public function testExecuteContinuesWhenCleanerReturnsNull(): void
    {
        $cleaner1 = $this->createMock(CleanerInterface::class);
        $cleaner1->method('isAvailable')->willReturn(true);
        $cleaner1->method('clean')->willReturn(null);

        $cleaner2 = $this->createMock(CleanerInterface::class);
        $cleaner2->method('isAvailable')->willReturn(true);
        $cleaner2->method('clean')->willReturn('success');

        $strategy = new FirstMatchStrategy();
        $result = $strategy->execute([$cleaner1, $cleaner2], 'input', 'UTF-8', []);

        $this->assertSame('success', $result);
    }
}
