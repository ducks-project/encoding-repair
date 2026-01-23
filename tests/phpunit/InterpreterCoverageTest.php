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
use Ducks\Component\EncodingRepair\Interpreter\PropertyMapperInterface;
use Ducks\Component\EncodingRepair\Interpreter\StringInterpreter;
use PHPUnit\Framework\TestCase;

final class InterpreterCoverageTest extends TestCase
{
    public function testStringInterpreterSupports(): void
    {
        $interpreter = new StringInterpreter();

        $this->assertTrue($interpreter->supports('test'));
        $this->assertFalse($interpreter->supports(123));
        $this->assertFalse($interpreter->supports([]));
        $this->assertFalse($interpreter->supports(new \stdClass()));
    }

    public function testStringInterpreterInterpret(): void
    {
        $interpreter = new StringInterpreter();

        $transcoder = static fn(string $data): string => \strtoupper($data);

        $result = $interpreter->interpret('test', $transcoder, []);

        $this->assertSame('TEST', $result);
    }

    public function testStringInterpreterGetPriority(): void
    {
        $interpreter = new StringInterpreter();

        $this->assertSame(100, $interpreter->getPriority());
    }

    public function testArrayInterpreterSupports(): void
    {
        $chain = new InterpreterChain();
        $interpreter = new ArrayInterpreter($chain);

        $this->assertTrue($interpreter->supports([]));
        $this->assertTrue($interpreter->supports(['test']));
        $this->assertFalse($interpreter->supports('test'));
        $this->assertFalse($interpreter->supports(123));
    }

    public function testArrayInterpreterInterpret(): void
    {
        $chain = new InterpreterChain();
        $chain->register(new StringInterpreter(), 100);
        $interpreter = new ArrayInterpreter($chain);

        $transcoder = static fn(string $data): string => \strtoupper($data);

        $result = $interpreter->interpret(['test', 'hello'], $transcoder, []);

        $this->assertSame(['TEST', 'HELLO'], $result);
    }

    public function testArrayInterpreterGetPriority(): void
    {
        $chain = new InterpreterChain();
        $interpreter = new ArrayInterpreter($chain);

        $this->assertSame(50, $interpreter->getPriority());
    }

    public function testObjectInterpreterSupports(): void
    {
        $chain = new InterpreterChain();
        $interpreter = new ObjectInterpreter($chain);

        $this->assertTrue($interpreter->supports(new \stdClass()));
        $this->assertFalse($interpreter->supports('test'));
        $this->assertFalse($interpreter->supports([]));
        $this->assertFalse($interpreter->supports(123));
    }

    public function testObjectInterpreterInterpretWithDefaultMapping(): void
    {
        $chain = new InterpreterChain();
        $chain->register(new StringInterpreter(), 100);
        $interpreter = new ObjectInterpreter($chain);

        $obj = new \stdClass();
        $obj->name = 'test';
        $obj->value = 'hello';

        $transcoder = static fn(string $data): string => \strtoupper($data);

        $result = $interpreter->interpret($obj, $transcoder, []);

        $this->assertInstanceOf(\stdClass::class, $result);
        $this->assertSame('TEST', $result->name);
        $this->assertSame('HELLO', $result->value);
        $this->assertNotSame($obj, $result); // Ensure it's cloned
    }

    public function testObjectInterpreterInterpretWithCustomMapper(): void
    {
        $chain = new InterpreterChain();
        $chain->register(new StringInterpreter(), 100);
        $interpreter = new ObjectInterpreter($chain);

        $mapper = new class implements PropertyMapperInterface {
            /**
             * Mapping for test.
             *
             * @param object $object
             * @param callable $transcoder
             * @param array<string, mixed> $options
             *
             * @return object
             *
             * @psalm-param \stdClass&object{name: string, password: string} $object
             */
            public function map(object $object, callable $transcoder, array $options): object
            {
                $copy = clone $object;
                $copy->name = $transcoder($object->name);

                // Don't transcode 'secret' property
                return $copy;
            }
        };

        $interpreter->registerMapper(\stdClass::class, $mapper);

        $obj = new \stdClass();
        $obj->name = 'test';
        $obj->secret = 'password';

        $transcoder = static fn(string $data): string => \strtoupper($data);

        $result = $interpreter->interpret($obj, $transcoder, []);

        $this->assertSame('TEST', $result->name);
        $this->assertSame('password', $result->secret); // Not transcoded
    }

    public function testObjectInterpreterGetPriority(): void
    {
        $chain = new InterpreterChain();
        $interpreter = new ObjectInterpreter($chain);

        $this->assertSame(30, $interpreter->getPriority());
    }

    public function testInterpreterChainInterpretWithNoMatch(): void
    {
        $chain = new InterpreterChain();

        $transcoder = static fn(string $data): string => \strtoupper($data);

        // No interpreter registered, should return data as-is
        $result = $chain->interpret(123, $transcoder, []);

        $this->assertSame(123, $result);
    }

    public function testInterpreterChainGetObjectInterpreterWhenNotRegistered(): void
    {
        $chain = new InterpreterChain();

        $result = $chain->getObjectInterpreter();

        $this->assertNull($result);
    }

    public function testInterpreterChainGetObjectInterpreterWhenRegistered(): void
    {
        $chain = new InterpreterChain();
        $objectInterpreter = new ObjectInterpreter($chain);
        $chain->register($objectInterpreter, 30);

        $result = $chain->getObjectInterpreter();

        $this->assertSame($objectInterpreter, $result);
    }

    public function testArrayInterpreterWithMixedTypes(): void
    {
        $chain = new InterpreterChain();
        $chain->register(new StringInterpreter(), 100);
        $arrayInterpreter = new ArrayInterpreter($chain);
        $chain->register($arrayInterpreter, 50);

        $transcoder = static function ($data) {
            return \is_string($data) ? \strtoupper($data) : $data;
        };

        $data = ['test', 123, null];

        $result = $arrayInterpreter->interpret($data, $transcoder, []);

        $this->assertSame('TEST', $result[0]);
        $this->assertSame(123, $result[1]);
        $this->assertNull($result[2]);
    }

    public function testObjectInterpreterWithMixedProperties(): void
    {
        $chain = new InterpreterChain();
        $chain->register(new StringInterpreter(), 100);
        $objectInterpreter = new ObjectInterpreter($chain);
        $chain->register($objectInterpreter, 30);

        $transcoder = static function ($data) {
            return \is_string($data) ? \strtoupper($data) : $data;
        };

        $obj = new \stdClass();
        $obj->name = 'test';
        $obj->count = 123;
        $obj->flag = null;

        $result = $objectInterpreter->interpret($obj, $transcoder, []);

        $this->assertSame('TEST', $result->name);
        $this->assertSame(123, $result->count);
        $this->assertNull($result->flag);
    }
}
