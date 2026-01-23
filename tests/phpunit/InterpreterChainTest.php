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

use Ducks\Component\EncodingRepair\Interpreter\ArrayInterpreter;
use Ducks\Component\EncodingRepair\Interpreter\InterpreterChain;
use Ducks\Component\EncodingRepair\Interpreter\ObjectInterpreter;
use Ducks\Component\EncodingRepair\Interpreter\StringInterpreter;
use PHPUnit\Framework\TestCase;

final class InterpreterChainTest extends TestCase
{
    public function testInterpretWithNoMatch(): void
    {
        $chain = new InterpreterChain();

        $transcoder = static fn (string $data): string => \strtoupper($data);

        $result = $chain->interpret(123, $transcoder, []);

        $this->assertSame(123, $result);
    }

    public function testGetObjectInterpreterWhenNotRegistered(): void
    {
        $chain = new InterpreterChain();

        $result = $chain->getObjectInterpreter();

        $this->assertNull($result);
    }

    public function testGetObjectInterpreterWhenRegistered(): void
    {
        $chain = new InterpreterChain();
        $objectInterpreter = new ObjectInterpreter($chain);
        $chain->register($objectInterpreter, 30);

        $result = $chain->getObjectInterpreter();

        $this->assertSame($objectInterpreter, $result);
    }

    public function testGetObjectInterpreterDoesNotConsumeQueue(): void
    {
        $chain = new InterpreterChain();
        $objectInterpreter = new ObjectInterpreter($chain);
        $chain->register($objectInterpreter, 30);

        $obj1 = $chain->getObjectInterpreter();
        $obj2 = $chain->getObjectInterpreter();

        $this->assertSame($obj1, $obj2);
        $this->assertInstanceOf(ObjectInterpreter::class, $obj1);
    }

    public function testInterpretWorksAfterGetObjectInterpreter(): void
    {
        $chain = new InterpreterChain();
        $chain->register(new StringInterpreter(), 100);
        $chain->register(new ArrayInterpreter($chain), 50);
        $chain->register(new ObjectInterpreter($chain), 30);

        $objInterpreter = $chain->getObjectInterpreter();
        $this->assertNotNull($objInterpreter);

        $callback = static fn ($value) => \is_string($value) ? \strtoupper($value) : $value;
        $result = $chain->interpret('test', $callback, []);

        $this->assertSame('TEST', $result);
    }
}
