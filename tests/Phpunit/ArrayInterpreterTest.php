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

use Ducks\Component\EncodingRepair\Interpreter\ArrayInterpreter;
use Ducks\Component\EncodingRepair\Interpreter\InterpreterChain;
use Ducks\Component\EncodingRepair\Interpreter\StringInterpreter;
use PHPUnit\Framework\TestCase;

final class ArrayInterpreterTest extends TestCase
{
    public function testSupports(): void
    {
        $chain = new InterpreterChain();
        $interpreter = new ArrayInterpreter($chain);

        $this->assertTrue($interpreter->supports([]));
        $this->assertTrue($interpreter->supports(['test']));
        $this->assertFalse($interpreter->supports('test'));
        $this->assertFalse($interpreter->supports(123));
    }

    public function testInterpret(): void
    {
        $chain = new InterpreterChain();
        $chain->register(new StringInterpreter(), 100);
        $interpreter = new ArrayInterpreter($chain);

        $transcoder = static fn (string $data): string => \strtoupper($data);

        $result = $interpreter->interpret(['test', 'hello'], $transcoder, []);

        $this->assertSame(['TEST', 'HELLO'], $result);
    }

    public function testGetPriority(): void
    {
        $chain = new InterpreterChain();
        $interpreter = new ArrayInterpreter($chain);

        $this->assertSame(50, $interpreter->getPriority());
    }

    public function testWithMixedTypes(): void
    {
        $chain = new InterpreterChain();
        $chain->register(new StringInterpreter(), 100);
        $arrayInterpreter = new ArrayInterpreter($chain);
        $chain->register($arrayInterpreter, 50);

        $transcoder = static fn ($data) => \is_string($data) ? \strtoupper($data) : $data;

        $data = ['test', 123, null];

        $result = $arrayInterpreter->interpret($data, $transcoder, []);

        $this->assertSame('TEST', $result[0]);
        $this->assertSame(123, $result[1]);
        $this->assertNull($result[2]);
    }
}
