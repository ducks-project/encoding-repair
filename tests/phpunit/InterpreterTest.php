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

use Ducks\Component\EncodingRepair\CharsetProcessor;
use Ducks\Component\EncodingRepair\Interpreter\PropertyMapperInterface;
use PHPUnit\Framework\TestCase;

final class InterpreterTest extends TestCase
{
    public function testStringInterpreter(): void
    {
        $processor = new CharsetProcessor();
        $iso = \mb_convert_encoding('Café', 'ISO-8859-1', 'UTF-8');

        $result = $processor->toUtf8($iso, CharsetProcessor::ENCODING_ISO);

        $this->assertSame('Café', $result);
    }

    public function testArrayInterpreter(): void
    {
        $processor = new CharsetProcessor();
        $data = [
            'name' => \mb_convert_encoding('José', 'ISO-8859-1', 'UTF-8'),
            'city' => \mb_convert_encoding('São Paulo', 'ISO-8859-1', 'UTF-8'),
        ];

        $result = $processor->toUtf8($data, CharsetProcessor::ENCODING_ISO);

        $this->assertSame('José', $result['name']);
        $this->assertSame('São Paulo', $result['city']);
    }

    public function testObjectInterpreterDefault(): void
    {
        $processor = new CharsetProcessor();

        $obj = new \stdClass();
        $obj->name = \mb_convert_encoding('Café', 'ISO-8859-1', 'UTF-8');
        $obj->price = 10;

        $result = $processor->toUtf8($obj, CharsetProcessor::ENCODING_ISO);

        $this->assertInstanceOf(\stdClass::class, $result);
        $this->assertSame('Café', $result->name);
        $this->assertSame(10, $result->price);
        $this->assertNotSame($obj, $result);
    }

    public function testCustomPropertyMapper(): void
    {
        $processor = new CharsetProcessor();

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

                // password is NOT transcoded
                return $copy;
            }
        };

        $processor->registerPropertyMapper(\stdClass::class, $mapper);

        $obj = new \stdClass();
        $obj->name = \mb_convert_encoding('José', 'ISO-8859-1', 'UTF-8');
        $obj->password = \mb_convert_encoding('sécret', 'ISO-8859-1', 'UTF-8');

        $result = $processor->toUtf8($obj, CharsetProcessor::ENCODING_ISO);

        $this->assertSame('José', $result->name);
        // Password should remain unchanged (not converted)
        $this->assertSame($obj->password, $result->password);
    }

    public function testNestedStructures(): void
    {
        $processor = new CharsetProcessor();

        $data = [
            'user' => (object) [
                'name' => \mb_convert_encoding('Café', 'ISO-8859-1', 'UTF-8'),
                'items' => [
                    \mb_convert_encoding('thé', 'ISO-8859-1', 'UTF-8'),
                    \mb_convert_encoding('café', 'ISO-8859-1', 'UTF-8'),
                ],
            ],
        ];

        $result = $processor->toUtf8($data, CharsetProcessor::ENCODING_ISO);

        $this->assertSame('Café', $result['user']->name);
        $this->assertSame('thé', $result['user']->items[0]);
        $this->assertSame('café', $result['user']->items[1]);
    }
}
