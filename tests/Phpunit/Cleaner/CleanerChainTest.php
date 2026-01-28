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

use Ducks\Component\EncodingRepair\Cleaner\CleanerChain;
use Ducks\Component\EncodingRepair\Cleaner\CleanerInterface;
use Ducks\Component\EncodingRepair\Cleaner\FirstMatchStrategy;
use Ducks\Component\EncodingRepair\Cleaner\PipelineStrategy;
use Ducks\Component\EncodingRepair\Cleaner\TaggedStrategy;
use PHPUnit\Framework\TestCase;

final class CleanerChainTest extends TestCase
{
    public function testDefaultStrategyIsPipeline(): void
    {
        $chain = new CleanerChain();

        $cleaner1 = $this->createMock(CleanerInterface::class);
        $cleaner1->method('isAvailable')->willReturn(true);
        $cleaner1->method('getPriority')->willReturn(100);
        $cleaner1->method('clean')->willReturn('step1');

        $cleaner2 = $this->createMock(CleanerInterface::class);
        $cleaner2->method('isAvailable')->willReturn(true);
        $cleaner2->method('getPriority')->willReturn(50);
        $cleaner2->method('clean')->willReturn('step2');

        $chain->register($cleaner1);
        $chain->register($cleaner2);

        $result = $chain->clean('input', 'UTF-8', []);

        $this->assertSame('step2', $result);
    }

    public function testConstructorAcceptsCustomStrategy(): void
    {
        $chain = new CleanerChain(new FirstMatchStrategy());

        $cleaner1 = $this->createMock(CleanerInterface::class);
        $cleaner1->method('isAvailable')->willReturn(true);
        $cleaner1->method('getPriority')->willReturn(100);
        $cleaner1->method('clean')->willReturn('first');

        $cleaner2 = $this->createMock(CleanerInterface::class);
        $cleaner2->method('isAvailable')->willReturn(true);
        $cleaner2->method('getPriority')->willReturn(50);
        $cleaner2->expects($this->never())->method('clean');

        $chain->register($cleaner1);
        $chain->register($cleaner2);

        $result = $chain->clean('input', 'UTF-8', []);

        $this->assertSame('first', $result);
    }

    public function testSetStrategyChangesExecutionBehavior(): void
    {
        $chain = new CleanerChain(new PipelineStrategy());

        $cleaner = $this->createMock(CleanerInterface::class);
        $cleaner->method('isAvailable')->willReturn(true);
        $cleaner->method('getPriority')->willReturn(100);
        $cleaner->method('clean')->willReturn('cleaned');

        $chain->register($cleaner);

        $result1 = $chain->clean('input', 'UTF-8', []);
        $this->assertSame('cleaned', $result1);

        $chain->setStrategy(new FirstMatchStrategy());
        $result2 = $chain->clean('input', 'UTF-8', []);
        $this->assertSame('cleaned', $result2);
    }

    public function testRegisterWithTags(): void
    {
        $chain = new CleanerChain(new TaggedStrategy(['tag1']));

        $cleaner1 = $this->createMock(CleanerInterface::class);
        $cleaner1->method('isAvailable')->willReturn(true);
        $cleaner1->method('getPriority')->willReturn(100);
        $cleaner1->method('clean')->willReturn('cleaned1');

        $cleaner2 = $this->createMock(CleanerInterface::class);
        $cleaner2->method('isAvailable')->willReturn(true);
        $cleaner2->method('getPriority')->willReturn(50);
        $cleaner2->expects($this->never())->method('clean');

        $chain->register($cleaner1, null, ['tag1']);
        $chain->register($cleaner2, null, ['tag2']);

        $result = $chain->clean('input', 'UTF-8', []);

        $this->assertSame('cleaned1', $result);
    }

    public function testSetStrategyReregistersTagsForTaggedStrategy(): void
    {
        $chain = new CleanerChain(new PipelineStrategy());

        $cleaner = $this->createMock(CleanerInterface::class);
        $cleaner->method('isAvailable')->willReturn(true);
        $cleaner->method('getPriority')->willReturn(100);
        $cleaner->method('clean')->willReturn('cleaned');

        $chain->register($cleaner, null, ['tag1']);

        $taggedStrategy = new TaggedStrategy(['tag1']);
        $chain->setStrategy($taggedStrategy);

        $result = $chain->clean('input', 'UTF-8', []);

        $this->assertSame('cleaned', $result);
    }

    public function testUnregisterCleaner(): void
    {
        $chain = new CleanerChain();

        $cleaner = $this->createMock(CleanerInterface::class);
        $cleaner->method('isAvailable')->willReturn(true);
        $cleaner->method('getPriority')->willReturn(100);
        $cleaner->method('clean')->willReturn('cleaned');

        $chain->register($cleaner);
        $result1 = $chain->clean('input', 'UTF-8', []);
        $this->assertSame('cleaned', $result1);

        $chain->unregister($cleaner);
        $result2 = $chain->clean('input', 'UTF-8', []);
        $this->assertNull($result2);
    }

    public function testRegisterWithCustomPriority(): void
    {
        $chain = new CleanerChain(new FirstMatchStrategy());

        $cleaner1 = $this->createMock(CleanerInterface::class);
        $cleaner1->method('isAvailable')->willReturn(true);
        $cleaner1->method('getPriority')->willReturn(50);
        $cleaner1->expects($this->never())->method('clean');

        $cleaner2 = $this->createMock(CleanerInterface::class);
        $cleaner2->method('isAvailable')->willReturn(true);
        $cleaner2->method('getPriority')->willReturn(100);
        $cleaner2->method('clean')->willReturn('high-priority');

        $chain->register($cleaner1);
        $chain->register($cleaner2, 200);

        $result = $chain->clean('input', 'UTF-8', []);

        $this->assertSame('high-priority', $result);
    }
}
