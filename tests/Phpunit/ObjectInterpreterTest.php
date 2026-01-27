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

use Ducks\Component\EncodingRepair\Interpreter\InterpreterChain;
use Ducks\Component\EncodingRepair\Interpreter\ObjectInterpreter;
use Ducks\Component\EncodingRepair\Interpreter\PropertyMapperInterface;
use Ducks\Component\EncodingRepair\Interpreter\StringInterpreter;
use PHPUnit\Framework\TestCase;

final class ObjectInterpreterTest extends TestCase
{
    public function testSupports(): void
    {
        $chain = new InterpreterChain();
        $interpreter = new ObjectInterpreter($chain);

        $this->assertTrue($interpreter->supports(new \stdClass()));
        $this->assertFalse($interpreter->supports('test'));
        $this->assertFalse($interpreter->supports([]));
        $this->assertFalse($interpreter->supports(123));
    }

    public function testInterpretWithDefaultMapping(): void
    {
        $chain = new InterpreterChain();
        $chain->register(new StringInterpreter(), 100);
        $interpreter = new ObjectInterpreter($chain);

        $obj = new \stdClass();
        $obj->name = 'test';
        $obj->value = 'hello';

        $transcoder = static fn (string $data): string => \strtoupper($data);

        $result = $interpreter->interpret($obj, $transcoder, []);

        $this->assertInstanceOf(\stdClass::class, $result);
        $this->assertSame('TEST', $result->name);
        $this->assertSame('HELLO', $result->value);
        $this->assertNotSame($obj, $result);
    }

    public function testInterpretWithCustomMapper(): void
    {
        $chain = new InterpreterChain();
        $chain->register(new StringInterpreter(), 100);
        $interpreter = new ObjectInterpreter($chain);

        $mapper = new class () implements PropertyMapperInterface {
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

        $transcoder = static fn (string $data): string => \strtoupper($data);

        $result = $interpreter->interpret($obj, $transcoder, []);

        $this->assertSame('TEST', $result->name);
        $this->assertSame('password', $result->secret);
    }

    public function testGetPriority(): void
    {
        $chain = new InterpreterChain();
        $interpreter = new ObjectInterpreter($chain);

        $this->assertSame(30, $interpreter->getPriority());
    }

    public function testWithMixedProperties(): void
    {
        $chain = new InterpreterChain();
        $chain->register(new StringInterpreter(), 100);
        $objectInterpreter = new ObjectInterpreter($chain);
        $chain->register($objectInterpreter, 30);

        $transcoder = static fn ($data) => \is_string($data) ? \strtoupper($data) : $data;

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
